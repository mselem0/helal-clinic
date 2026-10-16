<?php

namespace Modules\AI\Filament\Resources;

use App\Support\GuardsClinicModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Modules\AI\Filament\Resources\KnowledgeChunkResource\Pages;
use Modules\AI\Models\KnowledgeChunk;
use Modules\AI\Services\AiAgent\DocumentIngestionService;
use Modules\AI\Services\AiAgent\EmbeddingService;

class KnowledgeChunkResource extends Resource
{
    use GuardsClinicModule;

    protected static ?string $model = KnowledgeChunk::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'AI Assistant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Knowledge Content')
                    ->schema([
                        Forms\Components\Select::make('source_type')
                            ->options([
                                'medical_protocol' => 'Medical Protocol',
                                'clinic_policy' => 'Clinic Policy',
                                'drug_info' => 'Drug Information',
                                'procedure' => 'Procedure Guidance',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('source_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('content')
                            ->required()
                            ->rows(10)
                            ->columnSpanFull()
                            ->helperText('Main content for the AI to learn.'),
                        Forms\Components\Textarea::make('content_ar')
                            ->rows(5)
                            ->columnSpanFull()
                            ->label('Arabic Version (Optional)'),
                    ]),

                Forms\Components\Section::make('Metadata & Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\KeyValue::make('metadata')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('source_type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('content')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source_type')
                    ->options([
                        'medical_protocol' => 'Medical Protocol',
                        'clinic_policy' => 'Clinic Policy',
                        'drug_info' => 'Drug Information',
                        'procedure' => 'Procedure Guidance',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('ingest')
                    ->label('Ingest Document')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\Select::make('source_type')
                            ->options([
                                'medical_protocol' => 'Medical Protocol',
                                'clinic_policy' => 'Clinic Policy',
                                'drug_info' => 'Drug Information',
                                'procedure' => 'Procedure Guidance',
                            ])
                            ->required(),
                        Forms\Components\FileUpload::make('document')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'])
                            ->storeFileNamesIn('original_filename'),
                    ])
                    ->action(function (array $data) {
                        $file = storage_path('app/public/'.$data['document']);
                        $mime = File::mimeType($file);

                        $ingestion = app(DocumentIngestionService::class);
                        $text = $ingestion->extractText($file, $mime);

                        if ($text) {
                            $chunks = $ingestion->chunkText($text);
                            $embeddingService = app(EmbeddingService::class);

                            foreach ($chunks as $content) {
                                KnowledgeChunk::create([
                                    'source_type' => $data['source_type'],
                                    'source_name' => $data['original_filename'] ?? 'Imported Document',
                                    'content' => $content,
                                    'embedding' => $embeddingService->embed($content),
                                    'is_active' => true,
                                ]);
                            }

                            Notification::make()
                                ->title('Success')
                                ->body(count($chunks).' knowledge chunks created.')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKnowledgeChunks::route('/'),
            'create' => Pages\CreateKnowledgeChunk::route('/create'),
            'edit' => Pages\EditKnowledgeChunk::route('/{record}/edit'),
        ];
    }
}
