<?php

// app/Filament/Resources/VideoResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoResource\Pages;
use App\Models\Video;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $modelLabel = 'فيديو توعوي';

    protected static ?string $pluralModelLabel = 'الفيديوهات التوعوية';

    protected static ?string $navigationGroup = 'المحتوى';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('uploaded_at')->label('تاريخ نشر الفيديو الفعلي')->maxDate(now()),
                Forms\Components\FileUpload::make('image_path')->label('صورة البطاقة')->disk('public')->directory('landing')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(51200),
                Forms\Components\TextInput::make('external_video_url')->label('رابط Vimeo')->url()->maxLength(500)->rules(['nullable', 'regex:~^https://(?:www\\.)?(?:vimeo\\.com/|player\\.vimeo\\.com/video/)[0-9]+(?:[/?#].*)?$~'])->helperText('رابط الفيديو فقط، بدون كود iframe. عند ملئه يُستخدم بدل الملف المحلي.'),
                Forms\Components\FileUpload::make('video_path')->label('ملف الفيديو')->disk('public')->directory('landing')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4'])->maxSize(51200),
                Forms\Components\TextInput::make('reel')->label('رقم ريل فيسبوك')->maxLength(1000),
                Forms\Components\TextInput::make('category')->label('التصنيف')->maxLength(1000),
                Forms\Components\TextInput::make('description')->label('وصف الفيديو')->maxLength(1000),

                Forms\Components\Section::make('بيانات الفيديو التوعوي')
                    ->description('إضافة الفيديوهات التعليمية التي ستظهر في قسم الفيديوهات بالموقع.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الفيديو')
                            ->required()
                            ->maxLength(255),
                        SpatieMediaLibraryFileUpload::make('video-file')->collection('video-file')
                            ->label('ملف الفيديو'),
                        SpatieMediaLibraryFileUpload::make('video-thumbnail')->collection('video-thumbnail')
                            ->label('الصورة المصغرة (الغلاف)'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('حالة التفعيل (ظاهر في الموقع)')
                            ->required()
                            ->default(true),
                        Forms\Components\Hidden::make('order')
                            ->default(0),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('video-thumbnail')
                    ->label('الغلاف'),
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعل؟')
                    ->boolean(),
                Tables\Columns\TextColumn::make('order')
                    ->label('الترتيب')
                    ->numeric()
                    ->sortable(),
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
            ->reorderable('order')
            ->defaultSort('order')
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
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }
}
