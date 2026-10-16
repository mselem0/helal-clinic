<?php

namespace Modules\Clinical\Filament\Resources;

use App\Support\GuardsClinicModule;
use Filament\Forms;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Clinical\Filament\Resources\PrintTemplateResource\Pages;
use Modules\Clinical\Models\PrintTemplate;

class PrintTemplateResource extends Resource
{
    use GuardsClinicModule;

    protected static ?string $model = PrintTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-printer';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'قوالب الطباعة';

    protected static ?string $title = 'قوالب الطباعة';

    protected static ?string $modelLabel = 'قالب طباعة';

    protected static ?string $pluralModelLabel = 'قوالب الطباعة';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('PrintTemplateTabs')
                    ->tabs([
                        // Tab 1: Basic Settings
                        Tabs\Tab::make('البيانات الأساسية')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('اسم القالب')
                                            ->placeholder('مثال: روشتة العيادة الملونة، روشتة ورق جاهز')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('slug')
                                            ->label('المعرف الفريد (Slug)')
                                            ->placeholder('يترك فارغاً للتوليد التلقائي')
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),
                                    ]),

                                Toggle::make('is_default')
                                    ->label('تعيين كقالب افتراضي')
                                    ->helperText('عند التفعيل، سيتم استخدام هذا القالب تلقائياً عند طباعة الروشتة لجميع المرضى.')
                                    ->default(false),
                            ]),

                        // Tab 2: Header Customization
                        Tabs\Tab::make('الترويسة والشعار (Header)')
                            ->icon('heroicon-o-window')
                            ->schema([
                                Toggle::make('hide_header')
                                    ->label('إخفاء الهيدر بالكامل عند الطباعة (الطباعة على ورق العيادة المروّس الجاهز)')
                                    ->helperText('فعّل هذا الخيار إذا كنت تمتلك بالفعل أوراق روشتات جاهزة ومطبوعة مسبقاً بالمطبعة وتضعها في الطابعة، لكي يقوم النظام بطباعة الأدوية والبيانات فقط في الأماكن الفارغة.')
                                    ->default(false)
                                    ->reactive(),

                                Section::make('الخيار الأول: تصميم ترويسة نصية تقليدية')
                                    ->description('اكتب تفاصيل عيادتك وتخصصك وسيتم تنسيقها تلقائياً بأعلى الروشتة.')
                                    ->hidden(fn (Forms\Get $get) => $get('hide_header'))
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Textarea::make('header_left_ar')
                                                    ->label('ترويسة اليمين - باللغة العربية')
                                                    ->placeholder("أ.د. محمد هلال\nاستشاري أمراض النساء والتوليد والمناظير والحقن المجهري\nمدرس بكلية الطب جامعة القاهرة")
                                                    ->helperText('سيتم تجاهل هذه النصوص في حال قمت برفع صورة ترويسة كاملة جاهزة بالأسفل.')
                                                    ->rows(4),

                                                Textarea::make('header_right_en')
                                                    ->label('ترويسة اليسار - باللغة الإنجليزية')
                                                    ->placeholder("Dr. Mohamed Helal Shahata\nAndrology & Infertility Consultant\nCairo University Hospital")
                                                    ->helperText('سيتم تجاهل هذه النصوص في حال قمت برفع صورة ترويسة كاملة جاهزة بالأسفل.')
                                                    ->rows(4),
                                            ]),

                                        FileUpload::make('logo_path')
                                            ->label('شعار العيادة الصغير (Logo)')
                                            ->helperText('شعار صغير يظهر بجانب نصوص الترويسة أعلاه.')
                                            ->image()
                                            ->directory('print-logos')
                                            ->disk('public')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('الخيار الثاني: رفع صورة ترويسة كاملة جاهزة (Full-Width Letterhead Image)')
                                    ->description('إذا كان لديك تصميم جاهز ومصمم كصورة لترويسة الروشتة، يمكنك رفعه هنا ليظهر بالعرض الكامل بأعلى الورقة.')
                                    ->hidden(fn (Forms\Get $get) => $get('hide_header'))
                                    ->collapsible()
                                    ->schema([
                                        FileUpload::make('header_image_path')
                                            ->label('صورة ترويسة جاهزة بالكامل')
                                            ->helperText('رفع صورة تصميم الترويسة بعرض كامل ليتم وضعها بأعلى الصفحة مباشرةً وتجاهل نصوص الترويسة التقليدية.')
                                            ->image()
                                            ->directory('print-headers')
                                            ->disk('public')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Tab 3: Footer Customization
                        Tabs\Tab::make('التذييل والفوتر (Footer)')
                            ->icon('heroicon-o-queue-list')
                            ->schema([
                                Toggle::make('hide_footer')
                                    ->label('إخفاء الفوتر بالكامل عند الطباعة')
                                    ->helperText('فعّل هذا الخيار لإلغاء طباعة العناوين والتفاصيل المطبوعة مسبقاً بأسفل الورقة.')
                                    ->default(false)
                                    ->reactive(),

                                Grid::make(2)
                                    ->schema([
                                        Textarea::make('footer_text_ar')
                                            ->label('تذييل اليمين (عربي) - الفروع والعناوين')
                                            ->placeholder("فرع المهندسين: 12 شارع البطل أحمد عبد العزيز\nفرع التجمع: عيادات ميديكال سنتر 1\nمواعيد العمل: يومياً من 5 إلى 10 مساءً")
                                            ->rows(4)
                                            ->hidden(fn (Forms\Get $get) => $get('hide_footer')),

                                        Textarea::make('footer_text_en')
                                            ->label('تذييل اليسار (إنجليزي) - أرقام الاتصال والتواصل')
                                            ->placeholder("Tel: 01023456789 / 01198765432\nEmail: dr.ragab@example.com\nFacebook: /DrMohamedRagabClinic")
                                            ->rows(4)
                                            ->hidden(fn (Forms\Get $get) => $get('hide_footer')),
                                    ]),
                            ]),

                        // Tab 4: Margins and Layout Dimensions
                        Tabs\Tab::make('هوامش الطباعة والأبعاد')
                            ->icon('heroicon-o-arrows-pointing-out')
                            ->schema([
                                Section::make('ضبط المسافات الهامشية (بالميليمتر - mm)')
                                    ->description('تعد هذه الهوامش حاسمة جداً عند استخدام ورق مروّس مسبقاً (Letterhead) لضمان أن تبدأ بيانات المريض والأدوية أسفل الترويسة المطبوعة تماماً وتنتهي قبل تذييل الورقة.')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('margin_top')
                                                    ->label('الهامش العلوي (mm)')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->required(),

                                                TextInput::make('margin_bottom')
                                                    ->label('الهامش السفلي (mm)')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->required(),

                                                TextInput::make('margin_left')
                                                    ->label('الهامش الأيسر (mm)')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->required(),

                                                TextInput::make('margin_right')
                                                    ->label('الهامش الأيمن (mm)')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->required(),
                                            ]),
                                    ]),
                            ]),

                        // Tab 5: Styling, Colors, Visuals
                        Tabs\Tab::make('التصميم والمظهر')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        ColorPicker::make('primary_color')
                                            ->label('اللون الأساسي')
                                            ->default('#002855')
                                            ->required(),

                                        ColorPicker::make('secondary_color')
                                            ->label('اللون الثانوي (الفرعي)')
                                            ->default('#00B4D8')
                                            ->required(),

                                        Select::make('font_family')
                                            ->label('نوع الخط العربي')
                                            ->options([
                                                'Almarai' => 'Almarai (خط المراعي)',
                                                'Cairo' => 'Cairo (خط القاهرة)',
                                                'Tajawal' => 'Tajawal (خط تجول)',
                                            ])
                                            ->default('Almarai')
                                            ->required(),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        Toggle::make('show_patient_box')
                                            ->label('إظهار صندوق بيانات المريض الملون')
                                            ->default(true),

                                        Toggle::make('show_signature')
                                            ->label('إظهار مربع توقيع وختم الطبيب بالأسفل')
                                            ->default(true),

                                        Toggle::make('show_watermark')
                                            ->label('تفعيل العلامة المائية بالخلفية')
                                            ->default(false)
                                            ->reactive(),
                                    ]),

                                TextInput::make('watermark_text')
                                    ->label('نص العلامة المائية بالخلفية (مثال: اسم الطبيب أو الشعار)')
                                    ->placeholder('مثال: عيادة أ.د. محمد هلال')
                                    ->maxLength(100)
                                    ->hidden(fn (Forms\Get $get) => ! $get('show_watermark'))
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم القالب')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_default')
                    ->label('الافتراضي')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('hide_header')
                    ->label('إخفاء الهيدر')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('margins')
                    ->label('الهوامش (ع/س/ي/ش)')
                    ->getStateUsing(fn (PrintTemplate $record): string => "{$record->margin_top} / {$record->margin_bottom} / {$record->margin_right} / {$record->margin_left} (mm)"),

                ColorColumn::make('primary_color')
                    ->label('اللون الأساسي'),

                ColorColumn::make('secondary_color')
                    ->label('اللون الفرعي'),

                TextColumn::make('font_family')
                    ->label('الخط')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('معاينة الروشتة')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (PrintTemplate $record): string => route('admin.print-templates.preview', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrintTemplates::route('/'),
            'create' => Pages\CreatePrintTemplate::route('/create'),
            'edit' => Pages\EditPrintTemplate::route('/{record}/edit'),
        ];
    }
}
