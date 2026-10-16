<?php

namespace Modules\AccessControl\Filament\Resources;

use App\Support\GuardsClinicModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\AccessControl\Filament\Resources\RoleResource\Pages;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    use GuardsClinicModule;

    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $modelLabel = 'دور وظيفي';

    protected static ?string $pluralModelLabel = 'الأدوار والصلاحيات';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('البيانات الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الدور الوظيفي')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled(fn ($record) => in_array($record?->name, ['super_admin', 'panel_user']))
                            ->helperText('مثلاً: محاسب، مدير محتوى، سكرتارية'),
                        Forms\Components\TextInput::make('guard_name')
                            ->label('الحارس (Guard)')
                            ->default('web')
                            ->required()
                            ->hidden(),
                    ])->columns(2),

                Forms\Components\Section::make('الصلاحيات المتاحة (Modules)')
                    ->description('حدد الأقسام والمهام التي يمكن لصاحب هذا الدور القيام بها.')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                self::getModuleToggle('bookings', '📅 إدارة الحجوزات', 'عرض وإدارة حجوزات المرضى والمواعيد.'),
                                self::getModuleToggle('services', '🏥 الخدمات الطبية', 'إدارة الخدمات، العمليات، وفيديوهات التوعية.'),
                                self::getModuleToggle('content', '✍️ محتوى الموقع', 'تعديل بيانات الطبيب، الفروع، التقييمات، وتصميم الصفحات.'),
                                self::getModuleToggle('whatsapp', '💬 التواصل (واتساب)', 'إدارة قوالب الرسائل ومتابعة سجلات التواصل.'),
                                self::getModuleToggle('seo', '🔍 إعدادات SEO', 'تحسين محركات البحث، الكلمات المفتاحية، والتحويلات.'),
                                self::getModuleToggle('settings', '⚙️ الإعدادات والأمان', 'إدارة مستخدمي النظام، الأدوار، وسجل العمليات.'),
                            ]),
                    ]),
            ]);
    }

    protected static function getModuleToggle(string $name, string $label, string $description): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make("modules.{$name}")
            ->label($label)
            ->helperText($description)
            ->onColor('success')
            ->columnSpan(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الدور')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'super_admin' => 'مدير النظام (كل شيء)',
                        'panel_user' => 'مستخدم لوحة التحكم (افتراضي)',
                        'booking_manager' => 'مدير الحجوزات',
                        'content_manager' => 'مدير المحتوى',
                        'marketing' => 'مسؤول التسويق',
                        default => $state
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'super_admin' ? 'danger' : 'primary'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('عدد الصلاحيات')
                    ->counts('permissions')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => in_array($record->name, ['super_admin', 'panel_user'])),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
