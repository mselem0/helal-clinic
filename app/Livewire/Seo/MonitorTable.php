<?php

namespace App\Livewire\Seo;

use App\Models\Seo404Monitor as MonitorModel;
use App\Models\SeoRedirect;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class MonitorTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(MonitorModel::query())
            ->defaultSort('hit_count', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('url')
                    ->label('الرابط المكسور')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hit_count')
                    ->label('الزيارات')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_resolved')
                    ->label('تم الحل؟')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('آخر ظهور')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('الحالة')
                    ->trueLabel('تم الحل')
                    ->falseLabel('لم يُحل')
                    ->default(false),
                Tables\Filters\Filter::make('high_traffic')
                    ->label('الأكثر زيارة (5+)')
                    ->query(fn ($query) => $query->where('hit_count', '>=', 5)),
            ])
            ->actions([
                Tables\Actions\Action::make('create_redirect')
                    ->label('إنشاء تحويل')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('source_url')
                            ->label('المصدر')
                            ->default(fn (MonitorModel $record) => $record->url)
                            ->required(),
                        Forms\Components\TextInput::make('target_url')
                            ->label('الهدف')
                            ->required(),
                        Forms\Components\Select::make('redirect_type')
                            ->label('النوع')
                            ->options(['301' => '301', '302' => '302'])
                            ->default('301'),
                    ])
                    ->action(function (MonitorModel $record, array $data) {
                        $redirect = SeoRedirect::create($data);
                        $record->update([
                            'is_resolved' => true,
                            'seo_redirect_id' => $redirect->id,
                        ]);

                        $path = '/'.ltrim($redirect->source_url, '/');
                        Cache::forget("redirect_{$path}");

                        Notification::make()->title('تم إنشاء التحويل بنجاح')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function render()
    {
        return view('livewire.seo.monitor-table');
    }
}
