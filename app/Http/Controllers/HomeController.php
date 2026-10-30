<?php

namespace App\Http\Controllers;

use App\Helpers\SiteSettingHelper;
use App\Jobs\SyncTibbinoBookingRequest;
use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\HeroSetting;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\SitePage;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\TvInterview;
use App\Models\Video;
use App\Models\WebsiteBookingRequest;
use App\Services\PageSeo;
use App\Services\SiteView;
use App\Services\ThemeDraftService;
use App\Services\TibbinoBooking;
use App\Support\BranchSchedule;
use App\Support\ClinicFeatures;
use App\Support\DisplayNumbers;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Booking\Models\Booking;
use Modules\PatientCore\Models\Patient;

class HomeController extends Controller
{
    public function index(Request $request, ThemeDraftService $drafts)
    {
        $page = SiteView::draft(SitePage::where('page_key', 'home')->firstOrFail());
        abort_unless($page->is_published, 404);

        return $this->renderSite($request, $drafts, $page);
    }

    public function renderSite(Request $request, ThemeDraftService $drafts, $page, array $extra = [])
    {
        abort_if($request->has('preview') && ! $request->hasValidSignature(), 403);
        $preview = $request->has('preview');
        $sections = $drafts->applyDrafts(PageSection::orderBy('order')->get())->filter->is_active->sortBy('order');
        $theme = SiteSetting::where('key', 'active_theme')->value('value') ?? 'helal_classic';
        if ($preview) {
            $theme = $request->query('theme', $theme);
        }
        abort_unless(isset(ThemeDraftService::THEMES[$theme]), 404);
        $setting = SiteSetting::where('key', 'theme_'.$theme)->first();
        $style = json_decode(($preview ? ($setting?->draft_data['value'] ?? null) : null) ?? $setting?->value ?? '{}', true);
        foreach (['primary' => '#006f76', 'accent' => '#ed8b19'] as $key => $fallback) {
            if (! preg_match('/^#[0-9a-f]{6}$/i', $style[$key] ?? '')) {
                $style[$key] = $fallback;
            }
        }
        $sections->each(function ($section) use ($style): void {
            $section->background_color = $style['sections'][$section->section_key]['background_color'] ?? null;
            $section->faqs = json_decode($section->body ?? '{}', true)['faqs'] ?? null;
            $section->content = collect(json_decode($section->body ?? '{}', true)['fields'] ?? [])->pluck('value', 'key')->all();
            if ($section->section_key === 'faq' && $section->faqs === null) {
                $section->faqs = collect([3, 5, 7])->map(fn ($i) => ['question' => $section->content['field_'.$i] ?? '', 'answer' => $section->content['field_'.($i + 1)] ?? ''])->filter(fn ($faq) => $faq['question'] !== '')->all();
            }
            if (! preg_match('/^#[0-9a-f]{6}$/i', $section->background_color ?? '')) {
                $section->background_color = null;
            }
        });

        $seo = PageSeo::data($page, $extra['item'] ?? null);
        View::share('site_seo', $seo);
        $pageKeys = collect($page->sections ?? [])->map(fn ($v) => is_array($v) ? $v['key'] : $v);
        $pageSections = $pageKeys->map(fn ($key) => $sections->firstWhere('section_key', $key))->filter();
        if ($page->page_key === 'home' && $pageKeys->isEmpty()) {
            $pageSections = $sections->whereNotIn('section_key', ['header', 'footer']);
        }

        $videoLibrary = Video::where('is_active', true)->orderBy('order')->get();
        $tvInterviews = TvInterview::where('is_active', true)->orderBy('order')->orderBy('id')->get();

        return response(DisplayNumbers::html(view($extra['siteView'] ?? ($page->page_key === 'home' ? 'helal.home' : 'helal.page'), array_merge([
            'page' => $page, 'pageKey' => $page->page_key,
            'contact' => SiteSettingHelper::group('contact'), 'whatsappNumber' => SiteSettingHelper::formatWhatsappNumber(SiteSettingHelper::get('wa_number', '201509011313')),
            'pageSections' => $pageSections, 'sections' => $sections, 'theme' => $theme, 'style' => $style,
            'videos' => $page->page_key === 'home' ? $videoLibrary->take(3) : $videoLibrary,
            'videoLibrary' => $videoLibrary,
            'tvInterviews' => $tvInterviews,
            'testimonials' => Testimonial::where('is_active', true)->orderBy('order')->when($page->page_key === 'home', fn ($q) => $q->limit(3))->get(),
            'articles' => Article::where('is_published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->latest()->when($page->page_key === 'home', fn ($q) => $q->limit(3))->get(),
            'services' => Service::where('is_active', true)->orderBy('order')->get(),
            'branches' => ClinicBranch::where('is_active', true)->orderBy('order')->get(),
            'journey' => json_decode($sections->firstWhere('section_key', 'your-journey')?->body ?? '{}', true)['stages'] ?? [],
            'bookingEnabled' => ClinicFeatures::enabled('Booking'), 'preview' => $preview,
        ], $extra))->render()))->header('Cache-Control', $preview ? 'private, no-store' : 'no-cache')->header('X-Robots-Tag', str_replace(',', ', ', $seo['robots']));
    }

    public function bookingConfirmation(Request $request, ThemeDraftService $drafts)
    {
        if (! $request->session()->has('booking_confirmation_stored')) {
            return redirect('/appointment')->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
        }
        $stored = $request->session()->pull('booking_confirmation_stored') === true;
        $branchWhatsapp = $request->session()->pull('booking_confirmation_branch_whatsapp');
        $page = new SitePage([
            'page_key' => 'booking-confirmation', 'slug' => 'booking-confirmation',
            'title' => 'تم استلام طلب الحجز', 'sections' => [], 'robots' => 'noindex_nofollow',
        ]);

        return $this->renderSite($request, $drafts, $page, [
            'siteView' => 'helal.booking-confirmation', 'bookingStored' => $stored, 'branchWhatsapp' => $branchWhatsapp,
        ])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function storeBooking(Request $request): JsonResponse
    {
        $phone = $request->input('patient_phone');
        if (is_string($phone)) {
            $phone = strtr($phone, array_combine(preg_split('//u', '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY), str_split('01234567890123456789')));
            $phone = preg_replace('/[\s()\-]/u', '', $phone);
            if (str_starts_with($phone, '00')) {
                $phone = substr($phone, 2);
            }
            if (str_starts_with($phone, '+')) {
                $phone = substr($phone, 1);
            }
            $request->merge(['patient_phone' => $phone]);
        }
        $data = $request->validate([
            'request_key' => ['nullable', 'uuid'],
            'booking_time' => ['nullable', 'date_format:H:i'],
            'visit_type' => ['nullable', 'in:first_visit,follow_up'],
            'visit_window' => ['required_with:visit_type', 'nullable', 'string', 'max:255'],
            'patient_name' => ['required', 'string', 'max:70'],
            'patient_phone' => ['required', 'string', 'regex:/^[0-9]{7,15}$/'],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Africa/Cairo')->toDateString()],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'clinic_branch_id' => ['required', 'integer', 'exists:clinic_branches,id'],
        ]);
        $data['request_key'] = $data['request_key'] ?? (string) Str::uuid();
        $service = Service::where('is_active', true)->findOrFail($data['service_id']);
        $fertilityAnswers = [];
        if ($service->slug === 'service-2') {
            $fertilityAnswers = Validator::make($request->only(['previous_pregnancy', 'pregnancy_related_surgery']), [
                'previous_pregnancy' => ['nullable', 'in:yes,no'],
                'pregnancy_related_surgery' => ['nullable', 'in:yes,no'],
            ])->validate();
        }
        $answerLabels = ['previous_pregnancy' => 'حمل سابق', 'pregnancy_related_surgery' => 'عملية مرتبطة بالحمل'];
        $answerParts = [];
        foreach ($answerLabels as $field => $label) {
            if (filled($fertilityAnswers[$field] ?? null)) {
                $answerParts[] = $label.': '.($fertilityAnswers[$field] === 'yes' ? 'نعم' : 'لا');
            }
        }
        $answerNotes = $answerParts ? 'إجابات المريضة عند الحجز: '.implode('، ', $answerParts) : null;
        $branch = ClinicBranch::where('is_active', true)->findOrFail($data['clinic_branch_id']);
        $availability = BranchSchedule::forDate($branch, $data['booking_date']);
        if (! $availability['available']) {
            throw ValidationException::withMessages(['booking_date' => 'الفرع غير متاح في اليوم المختار. اختاري يومًا آخر.']);
        }
        $expectedWindow = \App\Support\BookingVisitDetails::window($branch, $availability);
        $retry = !empty($data['request_key']) ? WebsiteBookingRequest::where('request_key', $data['request_key'])->first() : null;
        if (isset($data['visit_type']) && ($data['visit_window'] ?? '') !== ($retry?->visit_window ?? $expectedWindow)) {
            throw ValidationException::withMessages(['visit_window' => 'تغيرت مواعيد الفرع. حدّثي الصفحة وراجعي الموعد قبل تأكيد الحجز.']);
        }
        $data['visit_window'] = $retry?->visit_window ?? $expectedWindow;
        $number = SiteSettingHelper::formatWhatsappNumber(trim($branch->phone ?? ''));
        $persistRequest = function () use ($data, $service, $branch, $request, $answerNotes): void {
            $fields = collect($data)->except(['request_key', 'visit_window'])->all();
            $existing = isset($data['request_key']) ? WebsiteBookingRequest::where('request_key', $data['request_key'])->first() : null;
            if ($existing) {
                foreach ($fields as $field => $value) {
                    $actual = $field === 'booking_date' ? $existing->booking_date->toDateString() : $existing->$field;
                    abort_unless((string) $actual === (string) $value, 409, 'تعذر إعادة استخدام هذا الطلب. أعيدي فتح نموذج الحجز.');
                }

                return;
            }
            // Serialize with branch schedule edits, without limiting appointments at this time.
            $currentBranch = ClinicBranch::whereKey($branch->id)->lockForUpdate()->firstOrFail();
            abort_unless($currentBranch->is_active, 422);
            $currentWindow = BranchSchedule::forDate($currentBranch, $data['booking_date']);
            if (!$currentWindow['available'] || \App\Support\BookingVisitDetails::window($currentBranch, $currentWindow) !== $data['visit_window']) {
                throw ValidationException::withMessages(['booking_date' => 'تغيرت مواعيد الفرع. حدّثي الصفحة وراجعي الموعد.']);
            }
            \App\Support\BookingTime::validate($currentBranch, $data['booking_date'], $data['booking_time'] ?? null);
            WebsiteBookingRequest::create($data + [
                'service_name' => $service->name, 'branch_name' => $branch->name,
                'source_host' => in_array($request->getHost(), ['drmohamedhelal.com', 'www.drmohamedhelal.com', 'ads.drmohamedhelal.com'], true) ? $request->getHost() : 'website',
                'integration_state' => 'pending', 'admin_notes' => $answerNotes,
            ]);
            if (ClinicFeatures::enabled('Booking')) {
                abort_unless(class_exists(Booking::class), 503);
                $patient = Patient::firstOrCreate(['phone' => $data['patient_phone']], ['name' => $data['patient_name'], 'gender' => 'female']);
                Booking::create(collect($data)->only(['patient_name', 'patient_phone', 'booking_date', 'booking_time', 'service_id', 'clinic_branch_id'])->all() + ['patient_id' => $patient->id, 'user_id' => auth()->check() && auth()->user()->isPatient() ? auth()->id() : null, 'status' => 'new', 'source' => 'website']);
            }
        };
        try {
            DB::transaction($persistRequest);
        } catch (UniqueConstraintViolationException $exception) {
            // A concurrent retry may have committed the same UUID while we were writing.
            // Retry only that known key; other uniqueness errors remain storage failures.
            if (empty($data['request_key']) || ! WebsiteBookingRequest::where('request_key', $data['request_key'])->exists()) {
                throw $exception;
            }
            DB::transaction($persistRequest);
        }
        // The durable local request commits before any queue or upstream operation.
        // The scheduler can recover even when dispatch itself is unavailable.
        if (TibbinoBooking::enabled()) {
            try {
                SyncTibbinoBookingRequest::dispatch(WebsiteBookingRequest::where('request_key', $data['request_key'])->value('id'));
            } catch (\Throwable $exception) {
                // Keep the saved request pending; never log tokens or patient data.
            }
        }
        $savedBooking = !empty($data['request_key']) ? WebsiteBookingRequest::where('request_key', $data['request_key'])->first() : null;
        $visitWindow = $savedBooking?->visit_window ?: $data['visit_window'];
        $visitType = $savedBooking?->visit_type ?? ($data['visit_type'] ?? null);
        $stored = true;
        $request->session()->flash('booking_confirmation_stored', $stored);
        $typeLabel = \App\Support\BookingVisitDetails::TYPES[$visitType] ?? 'غير محدد';
        $timeLabel = $savedBooking?->booking_time ?? ($data['booking_time'] ?? null);
        $whenLabel = $timeLabel ? 'الساعة المختارة: '.$timeLabel.' بتوقيت القاهرة.' : 'مواعيد الزيارة المعروضة: '.$visitWindow.'.';
        $message = "مرحبًا د. محمد هلال، أرغب في طلب موعد باسم {$data['patient_name']}، رقم {$data['patient_phone']}، يوم {$data['booking_date']}، فرع {$branch->name}، خدمة {$service->name}. نوع الزيارة: {$typeLabel}. {$whenLabel} الموعد قيد التأكيد.";
        if ($answerParts) {
            $message .= ' '.implode('، ', $answerParts).'.';
        }
        $request->session()->flash('booking_confirmation_branch_whatsapp', preg_match('/^[0-9]{7,15}$/', $number) ? 'https://wa.me/'.$number.'?text='.rawurlencode($message) : null);

        return response()->json([
            'confirmation_url' => route('bookings.confirmation'),
            'stored' => $stored, 'status' => 'pending',
            'message' => 'تم استلام طلب الحجز. فريق العيادة هيتواصل معاك لتأكيد الموعد.',
        ]);
    }
}
