<?php

namespace App\Filament\Resources;

use App\Models\PageSection;
use App\Models\SitePage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class SitePageResource extends Resource
{
    protected static ?string $model = SitePage::class;

    protected static ?string $navigationGroup = 'الصفحات والقوائم';

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $modelLabel = 'صفحة';

    protected static ?string $pluralModelLabel = 'صفحات الموقع';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('page_ThemeEditor') ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('محتوى الصفحة')->description('عدّل المحتوى واحفظ المسودة، ثم عاينها وانشرها من أزرار الصفحة.')->schema([
                Forms\Components\TextInput::make('title')->label('عنوان الصفحة')->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->label('الرابط')->visible(fn ($record) => ! $record)->required()->maxLength(180)->unique(ignoreRecord: true)->regex('/^[\pL\pN]+(?:[-_][\pL\pN]+)*$/u')->disabled(fn ($record) => in_array($record?->page_key, ['home', 'services', 'articles', 'branches']))->dehydrated(),
                Forms\Components\Textarea::make('intro')->label('مقدمة قصيرة'),
                Forms\Components\RichEditor::make('body')->label('محتوى إضافي')->fileAttachmentsDisk('public')->fileAttachmentsDirectory('landing'),
                Forms\Components\Section::make('ترتيب أقسام الصفحة')->collapsible()->collapsed()->schema([Forms\Components\Repeater::make('sections')->defaultItems(0)->label('أقسام الصفحة بالترتيب')->schema([Forms\Components\Select::make('key')->label('القسم')->options(fn ($record) => PageSection::whereNotIn('section_key', $record?->page_key === 'home' ? ['header', 'footer'] : ['header', 'footer', 'home'])->pluck('section_label', 'section_key'))->required()->distinct()])->reorderable()->default([])->helperText('عدّل النصوص والصور من زر «نصوص وصور الصفحة».')]),
                Forms\Components\Toggle::make('is_published')->label('إظهار الصفحة بعد النشر')->default(false),
            ]),
            Forms\Components\Section::make('المؤهلات والتخصصات والعضويات')->description('تظهر في صفحة عن الدكتور مع ملخص في الرئيسية. الحفظ مسودة، والنشر من استوديو الموقع.')->visible(fn ($record) => $record?->page_key === 'about')->schema([
                Forms\Components\Repeater::make('doctor_profile.official_links')->label('الحسابات الرسمية')->helperText('روابط الحسابات التابعة للدكتور والعيادة فقط؛ تظهر للزوار ولمحركات البحث بعد النشر.')->schema([
                    Forms\Components\TextInput::make('label')->label('اسم المنصة')->required()->maxLength(100),
                    Forms\Components\TextInput::make('url')->label('رابط الحساب')->url()->regex('/^https:\/\//i')->required()->maxLength(2000),
                ])->defaultItems(0)->reorderable(),
                Forms\Components\FileUpload::make('doctor_profile.image')->label('صورة التعريف بجوار المؤهلات')->image()->disk('public')->directory('landing'),
                Forms\Components\TextInput::make('doctor_profile.headline')->label('الصفة المهنية')->maxLength(255),
                Forms\Components\Repeater::make('doctor_profile.qualifications')->label('المؤهلات العلمية')->schema([
                    Forms\Components\TextInput::make('title')->label('المؤهل')->required()->maxLength(255),
                    Forms\Components\TextInput::make('detail')->label('الجامعة أو الجهة')->maxLength(255),
                ])->defaultItems(0)->reorderable()->collapsible(),
                Forms\Components\Repeater::make('doctor_profile.memberships')->label('العضويات المهنية')->schema([
                    Forms\Components\TextInput::make('title')->label('اسم الجمعية')->required()->maxLength(255),
                ])->defaultItems(0)->reorderable(),
                Forms\Components\Repeater::make('doctor_profile.specialties')->label('مجالات التخصص')->schema([
                    Forms\Components\TextInput::make('title')->label('مجال التخصص')->required()->maxLength(255),
                ])->defaultItems(0)->reorderable(),
            ]),
            Forms\Components\Section::make('محركات البحث والمشاركة')->hidden()->schema([
                Forms\Components\Placeholder::make('public_updated_at')->label('آخر تحديث للمحتوى المنشور')->content(fn (?SitePage $record) => $record?->public_updated_at?->format('Y-m-d H:i') ?? 'لم يُسجل تحديث منشور بعد')->helperText('يُحدّث تلقائيًا عند تغيير المحتوى المنشور؛ حفظ المسودة لا يغيّره.'),
                Forms\Components\TextInput::make('meta_title')->label('عنوان البحث')->maxLength(255),
                Forms\Components\Textarea::make('meta_description')->label('وصف البحث')->maxLength(320),
                Forms\Components\FileUpload::make('og_image')->label('صورة المشاركة')->image()->disk('public')->directory('landing'),
                Forms\Components\TextInput::make('canonical_url')->label('الرابط الأساسي الاختياري')->url()->maxLength(2000),
                Forms\Components\Select::make('robots')->label('الفهرسة')->options(['index_follow' => 'السماح بالفهرسة', 'noindex_follow' => 'عدم الفهرسة'])->default('index_follow')->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('الصفحة')->searchable(),
            Tables\Columns\TextColumn::make('slug')->label('الرابط'),
            Tables\Columns\IconColumn::make('is_published')->label('منشورة')->boolean(),
            Tables\Columns\TextColumn::make('draft_data')->label('المسودة')->formatStateUsing(fn ($record) => $record->draft_data ? 'تعديلات غير منشورة' : 'لا توجد'),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\Action::make('preview')->label('معاينة')->url(fn ($record) => URL::temporarySignedRoute('site.preview', now()->addMinutes(30), ['page' => $record->id, 'preview' => 1]))->openUrlInNewTab()]);
    }

    public static function getPages(): array
    {
        return ['index' => SitePageResource\Pages\ListSitePages::route('/'), 'create' => SitePageResource\Pages\CreateSitePage::route('/create'), 'edit' => SitePageResource\Pages\EditSitePage::route('/{record}/edit')];
    }
}
