<?php

namespace App\Filament\Pages;

use App\Models\PageSection;
use App\Models\SitePage;
use App\Models\SiteSetting;
use App\Models\ThemeRevision;
use App\Services\ContentReadiness;
use App\Services\ThemeDraftService;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class ThemeEditor extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationGroup = 'المظهر والثيمات';

    protected static ?string $navigationLabel = 'المحتوى والثيمات';

    protected static ?string $title = 'استوديو موقع هلال';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.theme-editor';

    public ?array $data = [];

    public string $selectedPageKey = 'home';

    public ?int $selectedSectionId = null;

    public string $editingTheme = 'helal_classic';

    public ?int $revisionId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('page_ThemeEditor') ?? false;
    }

    private function authorizeEditor(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function mount(): void
    {
        $this->authorizeEditor();
        $this->editingTheme = SiteSetting::where('key', 'active_theme')->value('value') ?? 'helal_classic';
        $section = PageSection::orderBy('order')->first();
        if ($section) {
            $this->selectSection($section->id);
        }
    }

    public function updatedSelectedPageKey(): void
    {
        $this->authorizeEditor();
        $page = SitePage::where('page_key', $this->selectedPageKey)->firstOrFail();
        $keys = collect($page->draft_data['sections'] ?? $page->sections ?? [])->map(fn ($v) => is_array($v) ? $v['key'] : $v);
        $section = PageSection::when($page->page_key !== 'home', fn ($q) => $q->whereIn('section_key', $keys))->orderBy('order')->first();
        $this->selectedSectionId = $section?->id;
        if ($section) {
            $this->selectSection($section->id);
        }
    }

    public function selectSection(int $id): void
    {
        $this->authorizeEditor();
        $this->selectedSectionId = $id;
        $section = app(ThemeDraftService::class)->applyDrafts(PageSection::findOrFail($id));
        // The editor always reads the draft; the public view only reads it with a valid preview signature.
        $draft = $section->draft_data ?? [];
        $body = json_decode($draft['body'] ?? $section->body ?? '{}', true) ?: [];
        $setting = SiteSetting::where('key', 'theme_'.$this->editingTheme)->first();
        $style = json_decode($setting?->draft_data['value'] ?? $setting?->value ?? '{}', true) ?: [];
        $this->form->fill([
            'fields' => $section->section_key === 'faq' ? array_values(array_filter($body['fields'] ?? [], fn ($field) => ! in_array($field['key'], ['field_3', 'field_4', 'field_5', 'field_6', 'field_7', 'field_8']))) : ($body['fields'] ?? []), 'faqs' => $body['faqs'] ?? $this->legacyFaqs($body), 'stages' => collect($body['stages'] ?? [])->map(fn ($value, $key) => ['key' => $key] + $value)->values()->all(), 'order' => $draft['order'] ?? $section->order,
            'is_active' => $draft['is_active'] ?? $section->is_active,
            'background_color' => $style['sections'][$section->section_key]['background_color'] ?? null,
            'primary' => $style['primary'] ?? '#006f76', 'accent' => $style['accent'] ?? '#ed8b19',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('محتوى القسم')->description('التعديلات مسودة حتى الضغط على نشر. راجعي بيانات العيادة قبل النشر.')->schema([
                Toggle::make('is_active')->label('إظهار القسم'),
                TextInput::make('order')->label('ترتيب القسم')->integer()->required(),
                ColorPicker::make('background_color')->label('خلفية القسم')->hex(),
                Repeater::make('fields')->label('النصوص والروابط والوسائط')->schema([
                    Hidden::make('key'), Hidden::make('type'), Hidden::make('label'),
                    Textarea::make('value')->label(fn ($get) => $get('label') ?: 'المحتوى')->rows(2)->maxLength(5000)->visible(fn ($get) => $get('type') === 'text'),
                    TextInput::make('value')->label('الرابط')->maxLength(2000)->visible(fn ($get) => $get('type') === 'url')->rules(['regex:~^(https?://|tel:|mailto:|/|\#|$)~i']),
                    TextInput::make('value')->label('مسار الصورة أو الفيديو')->maxLength(2000)->visible(fn ($get) => $get('type') === 'image')->helperText('ارفعي ملفًا أدناه أو استخدمي رابط https.'),
                    FileUpload::make('upload')->label('رفع صورة أو فيديو')->disk('public')->directory('landing')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4'])->maxSize(51200)->visible(fn ($get) => $get('type') === 'image'),
                ])->itemLabel(fn (array $state): string => $state['label'] ?? 'محتوى')->collapsible()->collapsed()->addable(false)->deletable(false)->reorderable(false),
            ]),
            Section::make('الأسئلة الشائعة')->visible(fn () => PageSection::find($this->selectedSectionId)?->section_key === 'faq')->schema([
                Repeater::make('faqs')->label('الأسئلة')->addActionLabel('إضافة سؤال')->schema([
                    TextInput::make('question')->label('السؤال')->required()->maxLength(500),
                    Textarea::make('answer')->label('الإجابة')->required()->maxLength(3000),
                ])->reorderable(),
            ]),
            Section::make('مراحل رحلة المريضة')->visible(fn () => PageSection::find($this->selectedSectionId)?->section_key === 'your-journey')->schema([
                Repeater::make('stages')->label('المراحل')->schema([
                    Hidden::make('key'), TextInput::make('tag')->label('اسم المرحلة')->required(),
                    TextInput::make('title')->label('العنوان')->required(), Textarea::make('description')->label('الوصف'),
                    TextInput::make('service')->label('اسم الخدمة للحجز'),
                    Repeater::make('questions')->label('أسئلة الزيارة')->simple(TextInput::make('question')->label('السؤال')),
                    TextInput::make('reel')->label('رقم فيديو فيسبوك')->numeric()->nullable(),
                    TextInput::make('video')->label('عنوان الفيديو'), Textarea::make('note')->label('ملاحظة الفيديو'),
                ])->addable(false)->deletable(false)->itemLabel(fn (array $state) => $state['tag'] ?? 'مرحلة')->collapsible(),
            ]),
            Section::make('مظهر الثيم المحدد')->schema([
                ColorPicker::make('primary')->label('اللون الأساسي')->required()->hex(),
                ColorPicker::make('accent')->label('لون التمييز')->required()->hex(),
            ])->columns(2),
        ])->statePath('data');
    }

    private function legacyFaqs(array $body): array
    {
        if (PageSection::find($this->selectedSectionId)?->section_key !== 'faq') {
            return [];
        }
        $fields = collect($body['fields'] ?? [])->pluck('value', 'key');

        return collect([3, 5, 7])->map(fn ($i) => ['question' => $fields['field_'.$i] ?? '', 'answer' => $fields['field_'.($i + 1)] ?? ''])->filter(fn ($faq) => $faq['question'] !== '')->values()->all();
    }

    public function editThemeOnly(string $themeKey): void
    {
        $this->authorizeEditor();
        abort_unless(isset(ThemeDraftService::THEMES[$themeKey]), 422);
        $this->editingTheme = $themeKey;
        if ($this->selectedSectionId) {
            $this->selectSection($this->selectedSectionId);
        }
    }

    public function save(): void
    {
        $this->authorizeEditor();
        abort_unless(isset(ThemeDraftService::THEMES[$this->editingTheme]), 422);
        $state = $this->form->getState();
        $section = PageSection::findOrFail($this->selectedSectionId);
        // Keep field definitions server-owned, so Livewire payloads cannot inject arbitrary bindings.
        $original = json_decode($section->body, true)['fields'] ?? [];
        $values = collect($state['fields'])->keyBy('key');
        foreach ($original as &$field) {
            $value = $values->get($field['key'], []);
            $field['value'] = $value['upload'] ?? $value['value'] ?? $field['value'];
        }
        unset($field);
        app(ThemeDraftService::class)->saveSectionDraft($section->section_key, [
            'body' => json_encode(['fields' => $original, 'faqs' => $state['faqs'] ?? [], 'stages' => collect($state['stages'] ?? [])->mapWithKeys(function ($stage) {
                $key = $stage['key'];
                unset($stage['key']);

                return [$key => $stage];
            })->all()], JSON_UNESCAPED_UNICODE), 'order' => (int) $state['order'],
            'is_active' => (bool) $state['is_active'],
        ]);
        $styleSetting = SiteSetting::where('key', 'theme_'.$this->editingTheme)->firstOrFail();
        $style = json_decode($styleSetting->draft_data['value'] ?? $styleSetting->value ?? '{}', true) ?: [];
        $style['primary'] = $state['primary'];
        $style['accent'] = $state['accent'];
        $style['sections'][$section->section_key]['background_color'] = $state['background_color'];
        $styleSetting->update(['draft_data' => ['value' => json_encode($style)]]);
        Notification::make()->title('تم حفظ المسودة')->success()->send();
    }

    public function publish(): void
    {
        $this->authorizeEditor();
        if ($this->selectedSectionId) {
            $this->save();
        }
        abort_unless(isset(ThemeDraftService::THEMES[$this->editingTheme]), 422);
        SiteSetting::where('key', 'active_theme')->update(['draft_data' => ['value' => $this->editingTheme]]);
        app(ThemeDraftService::class)->publish();
        Notification::make()->title('تم نشر الموقع والثيم المحدد')->success()->send();
    }

    public function discardDrafts(): void
    {
        $this->authorizeEditor();
        app(ThemeDraftService::class)->discardDrafts();
        if ($this->selectedSectionId) {
            $this->selectSection($this->selectedSectionId);
        }
        Notification::make()->title('تم إلغاء المسودات دون تغيير المنشور')->success()->send();
    }

    public function restore(): void
    {
        $this->authorizeEditor();
        $this->validate(['revisionId' => ['required', 'integer', Rule::exists('theme_revisions', 'id')]]);
        app(ThemeDraftService::class)->restoreRevision($this->revisionId);
        $this->mount();
        Notification::make()->title('تم استعادة الإصدار')->success()->send();
    }

    public function getPreviewUrlProperty(): string
    {
        $page = SitePage::where('page_key', $this->selectedPageKey)->firstOrFail();

        return URL::temporarySignedRoute('site.preview', now()->addMinutes(30), ['page' => $page->id, 'preview' => 1, 'theme' => $this->editingTheme]);
    }

    public function getViewData(): array
    {
        $page = SitePage::where('page_key', $this->selectedPageKey)->firstOrFail();
        $keys = collect($page->draft_data['sections'] ?? $page->sections ?? [])->map(fn ($v) => is_array($v) ? $v['key'] : $v);

        return ['sitePages' => SitePage::all(), 'warnings' => app(ContentReadiness::class)->warnings(), 'sections' => PageSection::when($page->page_key !== 'home', fn ($q) => $q->whereIn('section_key', $keys))->orderBy('order')->get(), 'themes' => ThemeDraftService::THEMES, 'revisions' => ThemeRevision::latest()->limit(20)->get(), 'contentRoutes' => ['services' => 'filament.admin.resources.services.index', 'clinics' => 'filament.admin.resources.clinic-branches.index', 'videos' => 'filament.admin.resources.videos.index', 'reviews' => 'filament.admin.resources.testimonials.index', 'articles' => 'filament.admin.resources.articles.index'], 'selectedKey' => PageSection::find($this->selectedSectionId)?->section_key, 'publishedTheme' => SiteSetting::where('key', 'active_theme')->value('value')];
    }
}
