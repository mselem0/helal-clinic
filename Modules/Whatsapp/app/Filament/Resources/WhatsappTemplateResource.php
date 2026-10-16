<?php

// app/Filament/Resources/WhatsappTemplateResource.php

namespace Modules\Whatsapp\Filament\Resources;

use App\Support\GuardsClinicModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Modules\Whatsapp\Filament\Resources\WhatsappTemplateResource\Pages;
use Modules\Whatsapp\Models\WhatsappTemplate;

class WhatsappTemplateResource extends Resource
{
    use GuardsClinicModule;

    protected static ?string $model = WhatsappTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'قوالب الرسائل';

    protected static ?string $navigationGroup = 'مركز الواتساب';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات القالب')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم القالب')
                            ->required(),
                        Forms\Components\Select::make('category')
                            ->label('الفئة')
                            ->options(WhatsappTemplate::distinct()->pluck('category', 'category')->toArray())
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('category')
                                    ->label('اسم الفئة الجديدة')
                                    ->required(),
                            ])
                            ->createOptionUsing(fn (array $data): string => $data['category'])
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('مفعل')
                            ->default(true)
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('محتوى الرسالة')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('نص القالب')
                            ->placeholder('أهلاً {patient_name}...')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('cheat_sheet')
                            ->label('دليل المتغيرات المتاحة (انقر للإضافة)')
                            ->content(new HtmlString('
                                <script>
                                    function insertVar(varName) {
                                        const textarea = document.querySelector(\'textarea[id$=".content"]\');
                                        if (!textarea) return;
                                        
                                        const start = textarea.selectionStart;
                                        const end = textarea.selectionEnd;
                                        const text = textarea.value;
                                        const before = text.substring(0, start);
                                        const after = text.substring(end, text.length);
                                        
                                        textarea.value = before + varName + after;
                                        textarea.selectionStart = textarea.selectionEnd = start + varName.length;
                                        textarea.focus();
                                        
                                        // Trigger change for Livewire
                                        textarea.dispatchEvent(new Event(\'input\'));
                                    }
                                </script>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div class="p-2 border rounded bg-gray-50 dark:bg-gray-800 cursor-pointer hover:bg-primary-50 transition" onclick="insertVar(\'{patient_name}\')">
                                        <code class="text-primary-600 font-bold">{patient_name}</code>
                                        <p class="text-xs text-gray-500 mt-1">يُستبدل باسم المريض تلقائياً</p>
                                    </div>
                                    <div class="p-2 border rounded bg-gray-50 dark:bg-gray-800 cursor-pointer hover:bg-primary-50 transition" onclick="insertVar(\'{patient_phone}\')">
                                        <code class="text-primary-600 font-bold">{patient_phone}</code>
                                        <p class="text-xs text-gray-500 mt-1">يُستبدل برقم هاتف المريض</p>
                                    </div>
                                    <div class="p-2 border rounded bg-gray-50 dark:bg-gray-800 cursor-pointer hover:bg-primary-50 transition" onclick="insertVar(\'{clinic_phone}\')">
                                        <code class="text-primary-600 font-bold">{clinic_phone}</code>
                                        <p class="text-xs text-gray-500 mt-1">رقم واتساب العيادة من الإعدادات</p>
                                    </div>
                                    <div class="p-2 border rounded bg-gray-50 dark:bg-gray-800 cursor-pointer hover:bg-primary-50 transition" onclick="insertVar(\'{doctor_name}\')">
                                        <code class="text-primary-600 font-bold">{doctor_name}</code>
                                        <p class="text-xs text-gray-500 mt-1">د. محمد هلال</p>
                                    </div>
                                    <div class="p-2 border rounded bg-gray-50 dark:bg-gray-800 cursor-pointer hover:bg-primary-50 transition" onclick="insertVar(\'{date}\')">
                                        <code class="text-primary-600 font-bold">{date}</code>
                                        <p class="text-xs text-gray-500 mt-1">تاريخ اليوم</p>
                                    </div>
                                    <div class="p-2 border rounded bg-gray-50 dark:bg-gray-800 cursor-pointer hover:bg-primary-50 transition" onclick="insertVar(\'{time}\')">
                                        <code class="text-primary-600 font-bold">{time}</code>
                                        <p class="text-xs text-gray-500 mt-1">الوقت الحالي</p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2 italic">يمكنك استخدام أي متغيرات مخصصة أخرى مثل {appointment_time} وسيطلب منك النظام تعبئتها عند الاستخدام.</p>
                            ')),

                        Forms\Components\TagsInput::make('variables_used')
                            ->label('المتغيرات المكتشفة')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('سيتم استخراج المتغيرات تلقائياً عند الحفظ')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم القالب')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('الفئة')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعل')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListWhatsappTemplates::route('/'),
            'create' => Pages\CreateWhatsappTemplate::route('/create'),
            'edit' => Pages\EditWhatsappTemplate::route('/{record}/edit'),
        ];
    }
}
