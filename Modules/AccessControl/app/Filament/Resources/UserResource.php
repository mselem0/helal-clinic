<?php

// app/Filament/Resources/UserResource.php

namespace Modules\AccessControl\Filament\Resources;

use App\Models\User;
use App\Support\GuardsClinicModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Modules\AccessControl\Filament\Resources\UserResource\Pages;

class UserResource extends Resource
{
    use GuardsClinicModule;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'فريق العمل';

    protected static ?string $navigationGroup = 'الإعدادات';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الحساب')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم الكامل')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ])->columns(2),

                Forms\Components\Section::make('مسؤوليات الموظف')
                    ->description('حدد المهام التي سيكون هذا الموظف مسؤولاً عنها في النظام.')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->label('الأدوار الوظيفية')
                            ->relationship('roles', 'name')
                            ->columns(2)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('الدور الوظيفي')
                    ->badge(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخر ظهور')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reset_password')
                    ->label('تغيير كلمة المرور')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('new_password')
                            ->label('كلمة المرور الجديدة')
                            ->password()
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update(['password' => Hash::make($data['new_password'])]);
                        Notification::make()->title('تم تغيير كلمة المرور بنجاح')->success()->send();
                    }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
