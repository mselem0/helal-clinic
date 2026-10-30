<?php

namespace App\Filament\Resources\WebsiteBookingRequestResource\Pages;

use App\Filament\Resources\WebsiteBookingRequestResource;
use App\Filament\Resources\WebsiteBookingRequestResource\Pages\Concerns\ConnectsGoogleSheets;
use App\Models\ClinicBranch;
use App\Models\WebsiteBookingRequest;
use App\Services\WebsiteBookingRequestExport;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListWebsiteBookingRequests extends ListRecords
{
    use ConnectsGoogleSheets;

    protected static string $resource = WebsiteBookingRequestResource::class;

    protected static string $view = 'filament.resources.website-booking-requests.list';

    public ?string $branch = null;

    public function updateTableColumnState(string $column, string $record, mixed $input): mixed
    {
        abort_unless(WebsiteBookingRequestResource::canViewAny(), 403);
        try {
            $result = parent::updateTableColumnState($column, $record, $input);
            $notification = Notification::make()
                ->title(is_array($result) && isset($result['error']) ? 'تعذر حفظ الحالة، راجع الاختيار' : 'تم تحديث حالة الطلب');
            $notification = is_array($result) && isset($result['error']) ? $notification->danger() : $notification->success();
            $this->dispatch('notificationSent', notification: $notification->toArray());

            return $result;
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('notificationSent', notification: Notification::make()->title('تعذر حفظ الحالة، حاول مرة أخرى')->danger()->toArray());

            return ['error' => 'تعذر حفظ الحالة، حاول مرة أخرى'];
        }
    }

    public function updatedBranch(): void
    {
        $this->resetPage();
    }

    public function getBranches()
    {
        return ClinicBranch::query()->orderBy('order')->orderBy('id')->get();
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->when($this->branch, fn ($query) => $query->where('clinic_branch_id', $this->branch));
    }

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('كل الطلبات')->badge(WebsiteBookingRequest::count())];
        foreach (WebsiteBookingRequest::STATUSES as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->badge(WebsiteBookingRequest::where('status', $status)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', $status));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('export')->label('تصدير Excel')->icon('heroicon-o-arrow-down-tray')
            ->visible(fn () => WebsiteBookingRequestResource::canViewAny())
            ->action(function () {
                abort_unless(WebsiteBookingRequestResource::canViewAny(), 403);

                return app(WebsiteBookingRequestExport::class)->download($this->getFilteredTableQuery());
            }), ...$this->googleSheetsActions()];
    }
}
