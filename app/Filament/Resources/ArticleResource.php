<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'المحتوى';

    protected static ?string $modelLabel = 'مقال';

    protected static ?string $pluralModelLabel = 'المقالات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image_path')->label('صورة البطاقة')->disk('public')->directory('landing')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(51200),
                Forms\Components\TextInput::make('category')->label('التصنيف')->maxLength(1000),

                Forms\Components\Section::make('معلومات المقال')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان المقال')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')->regex('/^[\pL\pN]+(?:[-_][\pL\pN]+)*$/u')
                            ->label('الرابط (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\RichEditor::make('content')
                            ->label('المحتوى')
                            ->columnSpanFull(),

                        Forms\Components\SpatieMediaLibraryFileUpload::make('image')
                            ->label('الصورة البارزة')
                            ->collection('article-image')
                            ->image()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_published')
                            ->label('منشور')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('التأليف والمراجعة الطبية')->description('استخدمي أسماء ومصادر وتواريخ مراجعة فعلية فقط.')->schema([
                    Forms\Components\TextInput::make('author_name')->label('كاتب المقال')->maxLength(255),
                    Forms\Components\TextInput::make('medical_reviewer')->label('المراجع الطبي')->maxLength(255),
                    Forms\Components\DateTimePicker::make('published_at')->label('تاريخ النشر')->helperText('التاريخ المستقبلي يخفي المقال حتى موعده.'),
                    Forms\Components\DatePicker::make('reviewed_at')->label('آخر مراجعة طبية')->maxDate(now()),
                    Forms\Components\Repeater::make('sources')->defaultItems(0)->label('المصادر')->schema([Forms\Components\TextInput::make('title')->label('المصدر')->required(), Forms\Components\TextInput::make('url')->label('الرابط')->url()->required()]),
                    Forms\Components\Select::make('service_ids')->label('الخدمات المرتبطة')->multiple()->options(fn () => Service::pluck('name', 'id')),
                    Forms\Components\FileUpload::make('og_image')->label('صورة المشاركة')->image()->disk('public')->directory('landing'),
                ]),
                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Meta Title')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->maxLength(255),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('image')
                    ->label('الصورة')
                    ->collection('article-image'),
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التعديل')
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
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
