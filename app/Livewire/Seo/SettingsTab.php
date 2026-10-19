<?php

namespace App\Livewire\Seo;

use App\Helpers\SeoHelper;
use App\Models\SeoGlobalSetting;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class SettingsTab extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SeoGlobalSetting::all()->pluck('value', 'key')->toArray();

        foreach (['include_homepage', 'include_services', 'include_branches', 'include_articles', 'include_pages'] as $key) {
            $settings[$key] = in_array($settings[$key] ?? '1', ['1', 1, true, 'true'], true);
        }

        // Decode custom_scripts if it exists
        if (isset($settings['custom_scripts']) && is_string($settings['custom_scripts'])) {
            $settings['custom_scripts'] = json_decode($settings['custom_scripts'], true);
        }

        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('إعدادات عامة')
                            ->schema([
                                TextInput::make('site_name')->label('اسم الموقع')->helperText('الاسم المفضل في نتائج البحث؛ الاختيار النهائي لمحرك البحث.'),
                                FileUpload::make('site_favicon')->label('أيقونة الموقع في المتصفح والبحث')->image()->acceptedFileTypes(['image/png', 'image/webp', 'image/jpeg'])->rules(['dimensions:min_width=48,min_height=48,ratio=1/1'])->disk('public')->directory('seo')->helperText('صورة مربعة لا تقل عن 48×48. يفضل 96×96 أو أكبر. اتركيها فارغة لاستخدام شعار هلال الافتراضي؛ تحديث Google ليس فوريًا.'),
                                TextInput::make('site_tagline')->label('وصف الموقع (Tagline)'),
                                Select::make('separator_char')
                                    ->label('فاصل العناوين')
                                    ->options(['|' => '|', '-' => '—', '•' => '•', '>>' => '»']),
                                TextInput::make('homepage_title')->label('تنسيق عنوان الصفحة الرئيسية'),
                                Textarea::make('homepage_description')->label('وصف الصفحة الرئيسية'),
                                Toggle::make('nofollow_external_links')->label('إضافة nofollow للروابط الخارجية'),
                                Toggle::make('open_external_links_new_tab')->label('فتح الروابط الخارجية في نافذة جديدة'),
                            ]),
                        Tabs\Tab::make('السوشيال ميديا')
                            ->schema([
                                TextInput::make('facebook_page_url')->label('رابط صفحة فيسبوك'),
                                TextInput::make('twitter_username')->label('اسم مستخدم تويتر (بدون @)'),
                                TextInput::make('linkedin_url')->label('رابط لينكد إن'),
                                FileUpload::make('default_og_image')
                                    ->label('الصورة الافتراضية للمشاركة (1200×630)')
                                    ->disk('public')
                                    ->directory('seo')
                                    ->image()
                                    ->imageResizeTargetWidth(1200)
                                    ->imageResizeTargetHeight(630),
                            ]),
                        Tabs\Tab::make('Sitemap')
                            ->schema([
                                Toggle::make('sitemap_enabled')->label('تفعيل Sitemap')->live(),
                                Forms\Components\Grid::make(2)
                                    ->visible(fn ($get) => $get('sitemap_enabled'))
                                    ->schema([
                                        TextInput::make('posts_per_sitemap')->label('عدد الروابط في كل ملف')->numeric()->integer()->minValue(1)->maxValue(1000)->default(500)->helperText('يُقسّم الملف تلقائيًا عند تجاوز هذا العدد، بحد أقصى 1000 رابط لكل ملف.'),
                                        Select::make('homepage_priority')
                                            ->label('أولوية الصفحة الرئيسية')->helperText('قيمة اختيارية في Sitemap؛ Google لا يستخدمها كعامل ترتيب.')
                                            ->options(['1.0' => '1.0 (الأعلى)', '0.8' => '0.8', '0.5' => '0.5'])
                                            ->default('1.0'),
                                        Toggle::make('include_homepage')->label('تضمين الصفحة الرئيسية')->default(true),
                                        Toggle::make('include_services')->label('تضمين صفحات الخدمات')->default(true)->helperText('صفحة الخدمات وجميع تفاصيل الخدمات المنشورة.'),
                                        Toggle::make('include_branches')->label('تضمين صفحات الفروع')->default(true)->helperText('صفحة الفروع وجميع تفاصيل الفروع المفعلة.'),
                                        Toggle::make('include_articles')->label('تضمين المقالات')->default(true)->helperText('صفحة المقالات وتفاصيل المقالات المنشورة التي حان موعد نشرها.'),
                                        Toggle::make('include_pages')->label('تضمين الصفحات الأخرى')->default(true)->helperText('مثل عن الدكتور والتواصل؛ لا يشمل الرئيسية أو الخدمات أو المقالات أو الفروع.'),
                                        Toggle::make('include_images_sitemap')->label('تضمين الصور لـ Sitemap'),
                                        Toggle::make('include_videos_sitemap')->label('تضمين الفيديوهات لـ Sitemap'),
                                    ]),
                                Section::make('توليد Sitemap')
                                    ->schema([
                                        Placeholder::make('sitemap_link')
                                            ->label('رابط Sitemap')
                                            ->content(new HtmlString('<a href="/sitemap.xml" target="_blank" class="text-primary-600 underline">عرض sitemap.xml</a>')),
                                    ]),
                            ]),
                        Tabs\Tab::make('النشاط التجاري (Local Business)')
                            ->schema([
                                TextInput::make('business_name')->label('اسم النشاط التجاري'),
                                Select::make('business_type')
                                    ->label('نوع النشاط')
                                    ->options([
                                        'Physician' => 'طبيب',
                                        'MedicalClinic' => 'عيادة طبية',
                                        'MedicalOrganization' => 'مؤسسة طبية',
                                    ]),
                                TextInput::make('business_phone')->label('رقم الهاتف'),
                                TextInput::make('business_email')->label('البريد الإلكتروني'),
                                Textarea::make('business_address')->label('العنوان'),
                                TextInput::make('business_lat')->label('خط العرض (Latitude)'),
                                TextInput::make('business_lng')->label('خط الطول (Longitude)'),
                            ]),
                        Tabs\Tab::make('أدوات المشرفين و Robots.txt')
                            ->schema([
                                Section::make('ملف Robots.txt')
                                    ->description('القواعد الأساسية للإدارة والمعاينات تُضاف تلقائيًا. تُحفظ القواعد الإضافية في قاعدة البيانات.')
                                    ->schema([
                                        Textarea::make('robots_extra')
                                            ->label('قواعد إضافية لـ robots.txt')
                                            ->rows(6)
                                            ->string()
                                            ->maxLength(10000)
                                            ->rules([function () {
                                                return function (string $attribute, $value, \Closure $fail): void {
                                                    foreach (preg_split('/\R/', $value ?? '') as $line) {
                                                        if (trim($line) !== '' && ! preg_match('~^\s*(?:#|Disallow\s*:\s*/|Allow\s*:\s*/)~i', $line)) {
                                                            $fail('استخدمي قواعد Allow أو Disallow بمسار يبدأ بـ /، أو تعليقًا يبدأ بـ #.');
                                                            break;
                                                        }
                                                    }
                                                };
                                            }])->helperText('مثال: Disallow: /private/ . رابط Sitemap يُضاف تلقائيًا عند تفعيله. هذا الملف يوجه الزحف ولا يحمي البيانات؛ الوصول محمي بالصلاحيات.'),
                                    ]),
                                Section::make('Google Analytics (GA4)')
                                    ->description('اربط حساب جوجل لجلب البيانات مباشرة إلى لوحة التحكم.')
                                    ->schema([
                                        Placeholder::make('google_connection_status')
                                            ->label('حالة الاتصال')
                                            ->content(function () {
                                                $token = SeoHelper::getSetting('google_access_token');
                                                if ($token) {
                                                    return new HtmlString('<span class="text-success-600 font-bold flex items-center gap-2"><span class="w-2 h-2 bg-success-500 rounded-full animate-pulse"></span> متصل بحساب جوجل (API)</span>');
                                                }

                                                if (! Route::has('admin.google.connect')) {
                                                    return 'ربط Google غير متاح في هذه النسخة.';
                                                }

                                                return new HtmlString('<a href="'.route('admin.google.connect').'" class="bg-navy text-white px-4 py-2 rounded-lg text-xs font-bold inline-block hover:bg-black transition">ربط حساب جوجل الآن</a>');
                                            }),
                                        TextInput::make('google_property_id')->label('معرف الخاصية (Property ID)')
                                            ->placeholder('XXXXXXXXX')
                                            ->helperText('مطلوب لجلب الإحصائيات عبر API. تجده في: Admin → Property Settings → Property ID.'),

                                        TextInput::make('google_analytics_id')
                                            ->label('معرف القياس (Measurement ID)')
                                            ->placeholder('G-XXXXXXXXXX')
                                            ->helperText('مطلوب لتتبع الزوار في الموقع. يبدأ بـ G-.'),
                                    ])->columns(2),

                                Section::make('فيسبوك بيكسل (Facebook Pixel)')
                                    ->description('تتبع زوار الموقع وحسن أداء إعلانات فيسبوك.')
                                    ->schema([
                                        Placeholder::make('pixel_guide')
                                            ->label('كيفية الحصول عليه')
                                            ->content(new HtmlString('<div class="text-xs text-gray-500 bg-blue-50 p-2 rounded">اذهب لـ Events Manager ← Data Sources ← اختر الـ Pixel ← الإعدادات ← انسخ معرف Pixel ID (أرقام فقط).</div>')),
                                        TextInput::make('facebook_pixel_id')
                                            ->label('Pixel ID')
                                            ->placeholder('1234567890123456'),
                                    ]),

                                Section::make('Google Tag Manager (GTM)')
                                    ->description('أداة موحدة لإدارة جميع أكواد التتبع.')
                                    ->schema([
                                        Placeholder::make('gtm_guide')
                                            ->label('كيفية الحصول عليه')
                                            ->content(new HtmlString('<div class="text-xs text-gray-500 bg-orange-50 p-2 rounded">سجل في GTM ← أنشئ Container ← انسخ المعرف الموجود بالأعلى (يبدأ بـ GTM-).</div>')),
                                        TextInput::make('gtm_id')
                                            ->label('Container ID')
                                            ->placeholder('GTM-XXXXXXX'),
                                    ]),

                                TextInput::make('google_verification')->label('كود التحقق من Google Search Console')->helperText('أدخلي قيمة content فقط من وسم التحقق. بعد النشر أضيفي /sitemap.xml داخل Search Console. لا يحدث ربط أو إرسال تلقائي.'),
                                TextInput::make('bing_verification')->label('كود التحقق من Bing'),
                                TextInput::make('yandex_verification')->label('كود التحقق من Yandex'),
                                TextInput::make('baidu_verification')->label('كود التحقق من Baidu'),
                            ]),
                        Tabs\Tab::make('أكواد إضافية (Scripts)')
                            ->schema([
                                Forms\Components\Repeater::make('custom_scripts')
                                    ->label('أكواد التتبع المخصصة')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('اسم الكود (مثلاً: Google Tag Manager)')
                                            ->required(),
                                        Textarea::make('code')
                                            ->label('الكود البرمجي')
                                            ->required()
                                            ->rows(8)
                                            ->extraAttributes(['style' => 'font-family: monospace;']),
                                        Select::make('location')
                                            ->label('مكان الحقن')
                                            ->options([
                                                'header' => 'الهيدر (Header)',
                                                'footer' => 'الفوتر (Footer)',
                                            ])
                                            ->required()
                                            ->default('header'),
                                        Toggle::make('is_active')
                                            ->label('تفعيل')
                                            ->default(true),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                            ]),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function saveAction(): Action
    {
        return Action::make('save')
            ->label('حفظ الإعدادات')
            ->action(function () {
                $this->save();
            });
    }

    public function disconnectGoogleAction(): Action
    {
        return Action::make('disconnect_google')
            ->label('قطع الاتصال بجوجل')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn () => ! empty(SeoHelper::getSetting('google_access_token')))
            ->action(function () {
                SeoHelper::setSetting('google_access_token', null, 'verification');
                SeoHelper::setSetting('google_refresh_token', null, 'verification');
                Notification::make()->title('تم قطع الاتصال بجوجل')->success()->send();
            });
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            // Encode custom_scripts if it's an array
            if ($key === 'custom_scripts' && is_array($value)) {
                $value = json_encode($value);
            }

            $group = $this->getGroupForKey($key);
            SeoHelper::setSetting($key, $value, $group);
        }

        Notification::make()
            ->title('تم حفظ إعدادات SEO بنجاح')
            ->success()
            ->send();
    }

    private function getGroupForKey(string $key): string
    {
        if (in_array($key, ['site_name', 'site_tagline', 'separator_char', 'homepage_title', 'homepage_description', 'nofollow_external_links', 'open_external_links_new_tab'])) {
            return 'general';
        }
        if (in_array($key, ['facebook_page_url', 'twitter_username', 'linkedin_url', 'default_og_image'])) {
            return 'social';
        }
        if (in_array($key, ['sitemap_enabled', 'posts_per_sitemap', 'include_images_sitemap', 'include_videos_sitemap', 'include_homepage', 'include_services', 'include_branches', 'include_articles', 'include_pages', 'homepage_priority'])) {
            return 'sitemap';
        }
        if (in_array($key, ['business_name', 'business_type', 'business_phone', 'business_email', 'business_address', 'business_lat', 'business_lng'])) {
            return 'local_business';
        }
        if (in_array($key, ['robots_extra', 'google_verification', 'bing_verification', 'yandex_verification', 'baidu_verification', 'google_property_id', 'google_access_token', 'google_refresh_token', 'google_analytics_id', 'facebook_pixel_id', 'gtm_id'])) {
            return 'verification';
        }

        if ($key === 'custom_scripts') {
            return 'scripts';
        }

        return 'general';
    }

    public function render()
    {
        return view('livewire.seo.settings-tab');
    }
}
