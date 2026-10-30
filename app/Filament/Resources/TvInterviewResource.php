<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TvInterviewResource\Pages;
use App\Models\TvInterview;
use App\Rules\ValidYouTubeVideoUrl;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TvInterviewResource extends Resource
{
    protected static ?string $model = TvInterview::class;
    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationGroup = 'المحتوى';
    protected static ?string $modelLabel = 'لقاء تلفزيوني';
    protected static ?string $pluralModelLabel = 'اللقاءات التلفزيونية';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('عنوان اللقاء')->required()->maxLength(255),
            Forms\Components\Textarea::make('description')->label('وصف اختياري')->rows(3)->maxLength(1000),
            Forms\Components\TextInput::make('youtube_url')->label('رابط YouTube')->required()->maxLength(500)
                ->rules([new ValidYouTubeVideoUrl])->helperText('رابط الفيديو فقط؛ لا يُقبل كود iframe.'),
            Forms\Components\FileUpload::make('cover_path')->label('صورة الغلاف الاختيارية')->image()->disk('public')->directory('tv-interviews')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(10240),
            Forms\Components\Toggle::make('is_active')->label('ظاهر في الموقع')->default(true)->required(),
            Forms\Components\Hidden::make('order')->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('cover_path')->label('الغلاف')->disk('public'),
            Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable(),
            Tables\Columns\IconColumn::make('is_active')->label('مفعل؟')->boolean(),
            Tables\Columns\TextColumn::make('order')->label('الترتيب')->sortable(),
        ])->reorderable('order')->defaultSort('order')->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListTvInterviews::route('/'), 'create' => Pages\CreateTvInterview::route('/create'), 'edit' => Pages\EditTvInterview::route('/{record}/edit')];
    }
}
