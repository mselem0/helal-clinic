<?php

namespace Modules\AI\Filament\Pages;

use App\Support\GuardsClinicModule;
use Filament\Pages\Page;
use Modules\AI\Models\AiEmbedding;
use Modules\Booking\Models\Booking;
use Modules\PatientCore\Models\Patient;
use Modules\PatientCore\Models\VisitRecord;

class AiMemoryManagement extends Page
{
    use GuardsClinicModule;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string $view = 'filament.pages.ai-memory-management';

    protected static ?string $title = 'إدارة ذاكرة النظام';

    protected static ?string $navigationGroup = 'AI Assistant';

    public function getStats(): array
    {
        return [
            'total_records' => Patient::count() + Booking::count() + VisitRecord::count(),
            'synced_count' => AiEmbedding::where('status', 'synced')->count(),
            'pending_count' => AiEmbedding::where('status', 'pending')->count(),
            'failed_count' => AiEmbedding::where('status', 'failed')->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getSyncData(): array
    {
        return [
            ['name' => 'المرضى', 'model' => Patient::class, 'count' => Patient::count(), 'synced' => Patient::count()],
            ['name' => 'الحجوزات', 'model' => Booking::class, 'count' => Booking::count(), 'synced' => Booking::count()],
            ['name' => 'سجلات الزيارات', 'model' => VisitRecord::class, 'count' => VisitRecord::count(), 'synced' => VisitRecord::count()],
        ];
    }
}
