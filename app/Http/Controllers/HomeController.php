<?php

namespace App\Http\Controllers;

use App\Helpers\SiteSettingHelper;
use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\HeroSetting;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\SitePage;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\Video;
use App\Services\PageSeo;
use App\Services\SiteView;
use App\Services\ThemeDraftService;
use App\Support\ClinicFeatures;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
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

        return response(\App\Support\DisplayNumbers::html(view($page->page_key === 'home' ? 'helal.home' : 'helal.page', array_merge([
            'page' => $page, 'pageKey' => $page->page_key,
            'contact' => SiteSettingHelper::group('contact'), 'whatsappNumber' => SiteSettingHelper::get('wa_number', '201017900354'),
            'pageSections' => $pageSections, 'sections' => $sections, 'theme' => $theme, 'style' => $style,
            'videos' => $page->page_key === 'home' ? $videoLibrary->take(3) : $videoLibrary,
            'videoLibrary' => $videoLibrary,
            'testimonials' => Testimonial::where('is_active', true)->orderBy('order')->when($page->page_key === 'home', fn ($q) => $q->limit(3))->get(),
            'articles' => Article::where('is_published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->latest()->when($page->page_key === 'home', fn ($q) => $q->limit(3))->get(),
            'services' => Service::where('is_active', true)->orderBy('order')->get(),
            'branches' => ClinicBranch::where('is_active', true)->orderBy('order')->get(),
            'journey' => json_decode($sections->firstWhere('section_key', 'your-journey')?->body ?? '{}', true)['stages'] ?? [],
            'bookingEnabled' => ClinicFeatures::enabled('Booking'), 'preview' => $preview,
        ], $extra))->render()))->header('Cache-Control', $preview ? 'private, no-store' : 'no-cache')->header('X-Robots-Tag', str_replace(',', ', ', $seo['robots']));
    }

    public function storeBooking(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_name' => ['required', 'string', 'max:70'],
            'patient_phone' => ['required', 'regex:/^01[0125][0-9]{8}$/'],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Africa/Cairo')->toDateString()],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'clinic_branch_id' => ['required', 'integer', 'exists:clinic_branches,id'],
        ]);
        $service = Service::where('is_active', true)->findOrFail($data['service_id']);
        $branch = ClinicBranch::where('is_active', true)->findOrFail($data['clinic_branch_id']);
        $availability = \App\Support\BranchSchedule::forDate($branch, $data['booking_date']);
        if (! $availability['available']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['booking_date' => 'الفرع غير متاح في اليوم المختار. اختاري يومًا آخر.']);
        }
        $stored = false;
        if (ClinicFeatures::enabled('Booking')) {
            abort_unless(class_exists(Booking::class), 503);
            DB::transaction(function () use ($data): void {
                $patient = Patient::firstOrCreate(['phone' => $data['patient_phone']], ['name' => $data['patient_name'], 'gender' => 'female']);
                Booking::create($data + ['patient_id' => $patient->id, 'user_id' => auth()->check() && auth()->user()->isPatient() ? auth()->id() : null, 'status' => 'new', 'source' => 'website']);
            });
            $stored = true;
        }
        $number = preg_replace('/[^0-9]/', '', SiteSettingHelper::get('wa_number', HeroSetting::first()?->whatsapp_number ?? ''));
        abort_if($number === '', 503, 'رقم التواصل غير مُعدّ.');
        $hours = $availability['from'] && $availability['to'] ? ' مواعيد العيادة من '.$availability['from'].' إلى '.$availability['to'].' بتوقيت القاهرة.' : '';
        $message = "مرحبًا د. محمد هلال، أرغب في طلب موعد باسم {$data['patient_name']}، رقم {$data['patient_phone']}، يوم {$data['booking_date']}، فرع {$branch->name}، خدمة {$service->name}. الموعد قيد التأكيد.".$hours;

        return response()->json([
            'stored' => $stored, 'status' => 'pending',
            'message' => $stored ? 'تم تسجيل طلبكِ للمراجعة. الموعد غير مؤكد بعد.' : 'طلبكِ جاهز للإرسال عبر واتساب. لم يُسجل موعد على الموقع.',
            'whatsapp_url' => 'https://wa.me/'.$number.'?text='.rawurlencode($message),
        ]);
    }
}
