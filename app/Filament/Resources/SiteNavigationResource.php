<?php

namespace App\Filament\Resources;

use App\Models\SiteNavigation;
use App\Models\SitePage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class SiteNavigationResource extends Resource
{
    protected static ?string $model = SiteNavigation::class;

    protected static ?string $navigationGroup = 'الصفحات والقوائم';

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $modelLabel = 'قائمة';

    protected static ?string $pluralModelLabel = 'قوائم الموقع';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('page_ThemeEditor') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
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
            Forms\Components\Placeholder::make('notice')->label('حفظ كمسودة')->content('عدّلي ترتيب الروابط وتسميتها. النشر من استوديو الموقع. الصفحات المخفية لا تظهر للزوار.'),
            Forms\Components\Repeater::make('items')->label('روابط القائمة')->schema([
                Forms\Components\TextInput::make('label')->label('النص')->required()->maxLength(100),
                Forms\Components\Select::make('page_key')->label('الصفحة')->options(fn () => SitePage::pluck('title', 'page_key'))->required()->rules([Rule::exists('site_pages', 'page_key')]),
            ])->reorderable()->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('location')->label('موضع القائمة')->formatStateUsing(fn ($state) => $state === 'header' ? 'الهيدر' : 'الفوتر')])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => SiteNavigationResource\Pages\ListSiteNavigations::route('/'), 'edit' => SiteNavigationResource\Pages\EditSiteNavigation::route('/{record}/edit')];
    }
}
