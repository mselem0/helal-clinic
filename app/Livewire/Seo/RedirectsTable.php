<?php

namespace App\Livewire\Seo;

use App\Models\SeoRedirect;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class RedirectsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(SeoRedirect::query())
            ->columns([
                Tables\Columns\TextColumn::make('source_url')
                    ->label('المصدر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_url')
                    ->label('الهدف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('redirect_type')
                    ->label('النوع')
                    ->badge(),
                Tables\Columns\TextColumn::make('hit_count')
                    ->label('الزيارات')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('redirect_type')
                    ->options([
                        '301' => '301',
                        '302' => '302',
                        '410' => '410',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\TextInput::make('source_url')->required(),
                        Forms\Components\TextInput::make('target_url')->required(),
                        Forms\Components\Select::make('redirect_type')
                            ->options(['301' => '301', '302' => '302', '410' => '410'])
                            ->default('301'),
                        Forms\Components\Toggle::make('is_active')->default(true),
                    ])
                    ->after(function ($record) {
                        $oldPath = '/'.ltrim($record->getOriginal('source_url'), '/');
                        $newPath = '/'.ltrim($record->source_url, '/');
                        Cache::forget("redirect_{$oldPath}");
                        if ($oldPath !== $newPath) {
                            Cache::forget("redirect_{$newPath}");
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        $path = '/'.ltrim($record->source_url, '/');
                        Cache::forget("redirect_{$path}");
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function ($records) {
                            foreach ($records as $record) {
                                $path = '/'.ltrim($record->source_url, '/');
                                Cache::forget("redirect_{$path}");
                            }
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة تحويل جديد')
                    ->form([
                        Forms\Components\TextInput::make('source_url')->required(),
                        Forms\Components\TextInput::make('target_url')->required(),
                        Forms\Components\Select::make('redirect_type')
                            ->options(['301' => '301', '302' => '302', '410' => '410'])
                            ->default('301'),
                        Forms\Components\Toggle::make('is_active')->default(true),
                    ])
                    ->after(function ($record) {
                        $path = '/'.ltrim($record->source_url, '/');
                        Cache::forget("redirect_{$path}");
                    }),
            ]);
    }

    public function render()
    {
        return view('livewire.seo.redirects-table');
    }
}
