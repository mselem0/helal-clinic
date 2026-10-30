<?php

namespace App\Filament\Pages;

use App\Models\PageSection;
use App\Models\SitePage;
use App\Models\SiteSetting;
use App\Services\ThemeDraftService;
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

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = null;

    protected static ?string $navigationLabel = 'التحكم في المحتوى';

    protected static ?string $title = 'محتوى الموقع';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.theme-editor';

    public ?array $data = [];

    public string $selectedPageKey = 'home';

    protected $queryString = ['selectedPageKey' => ['except' => 'home']];

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
        if (! SitePage::where('page_key', $this->selectedPageKey)->exists()) {
            $this->selectedPageKey = SitePage::where('page_key', 'home')->value('page_key') ?? SitePage::value('page_key') ?? 'home';
        }
        $this->updatedSelectedPageKey();
    }

    public function updatedSelectedPageKey(): void
    {
        $this->authorizeEditor();
        $page = SitePage::where('page_key', $this->selectedPageKey)->firstOrFail();
        $sections = $this->sectionsForPage($page);
        $section = ($page->page_key === 'home' ? $sections->firstWhere('section_key', 'home') : null) ?? $sections->first();
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
        $this->form->fill([
            'fields' => array_values(array_filter($body['fields'] ?? [], fn ($field) => in_array($field['type'] ?? '', ['text', 'image'], true) && ($section->section_key !== 'faq' || ! in_array($field['key'], ['field_3', 'field_4', 'field_5', 'field_6', 'field_7', 'field_8'])))), 'faqs' => $body['faqs'] ?? $this->legacyFaqs($body), 'stages' => collect($body['stages'] ?? [])->map(fn ($value, $key) => ['key' => $key] + $value)->values()->all(),
            'is_active' => $draft['is_active'] ?? $section->is_active,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('محتوى القسم')->description('احفظ التعديلات، ثم عاينها وانشرها عندما تكون جاهزة.')->schema([
                Toggle::make('is_active')->label('إظهار القسم'),
                Repeater::make('fields')->label('النصوص والصور')->schema([
                    Hidden::make('key'), Hidden::make('type'), Hidden::make('label'),
                    Textarea::make('value')->label(fn ($get) => $get('label') ?: 'المحتوى')->rows(2)->maxLength(5000)->visible(fn ($get) => $get('type') === 'text'),
                    FileUpload::make('upload')->label('رفع صورة أو فيديو')->disk('public')->directory('landing')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4'])->maxSize(51200)->visible(fn ($get) => $get('type') === 'image'),
                ])->itemLabel(fn (array $state): string => $state['label'] ?? 'محتوى')->collapsible()->addable(false)->deletable(false)->reorderable(false),
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
                    TextInput::make('video')->label('عنوان الفيديو'), Textarea::make('note')->label('ملاحظة الفيديو'),
                ])->addable(false)->deletable(false)->itemLabel(fn (array $state) => $state['tag'] ?? 'مرحلة')->collapsible(),
            ]),
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
        // Merge edits into the latest draft so unexposed content and settings survive a save.
        $body = json_decode($section->draft_data['body'] ?? $section->body ?? '{}', true) ?: [];
        $original = $body['fields'] ?? [];
        $values = collect($state['fields'] ?? [])->keyBy('key');
        foreach ($original as &$field) {
            if (! in_array($field['type'] ?? '', ['text', 'image'], true)) {
                continue;
            }
            $value = $values->get($field['key'], []);
            $field['value'] = $value['upload'] ?? $value['value'] ?? $field['value'];
        }
        unset($field);
        $body['fields'] = $original;
        if ($section->section_key === 'faq' && array_key_exists('faqs', $state)) {
            $body['faqs'] = $state['faqs'];
        }
        if ($section->section_key === 'your-journey' && array_key_exists('stages', $state)) {
            $stages = $body['stages'] ?? [];
            foreach ($state['stages'] as $stage) {
                $key = $stage['key'];
                unset($stage['key']);
                // Stage identifiers and unexposed values remain server-owned.
                if (array_key_exists($key, $stages)) {
                    $stages[$key] = array_replace($stages[$key], $stage);
                }
            }
            $body['stages'] = $stages;
        }
        app(ThemeDraftService::class)->saveSectionDraft($section->section_key, [
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
            'is_active' => (bool) $state['is_active'],
        ]);
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
        Notification::make()->title('تم نشر الموقع')->success()->send();
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

    private function sectionsForPage(SitePage $page)
    {
        $keys = collect($page->draft_data['sections'] ?? $page->sections ?? [])
            ->map(fn ($value) => is_array($value) ? ($value['key'] ?? '') : $value);
        if ($page->page_key === 'home') {
            $keys->push('home');
        }

        return PageSection::whereIn('section_key', $keys)->whereNotIn('section_key', ['header', 'footer'])->orderBy('order')->get();
    }

    public function getViewData(): array
    {
        $page = SitePage::where('page_key', $this->selectedPageKey)->firstOrFail();

        return [
            'sitePages' => SitePage::all(),
            'sections' => $this->sectionsForPage($page),
        ];
    }
}
