<?php

namespace App\Filament\Pages;

use App\Support\ClinicFeatures;
use Filament\Pages\Page;

class ClinicSystemStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'حالة النظام والتكاملات';

    protected static ?string $title = 'حالة النظام الكامل';

    protected static string $view = 'filament.pages.clinic-system-status';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function getViewData(): array
    {
        $modules = [];
        foreach (['PatientCore' => 'المرضى', 'Booking' => 'الحجوزات', 'Clinical' => 'السجلات الطبية والروشتات', 'AccessControl' => 'المستخدمون والصلاحيات', 'ActivityLog' => 'سجل النشاط', 'Whatsapp' => 'إدارة واتساب', 'AI' => 'الذكاء الاصطناعي'] as $key => $label) {
            $modules[] = ['label' => $label, 'enabled' => ClinicFeatures::enabled($key), 'dependencies' => ClinicFeatures::DEPENDENCIES[$key] ?? []];
        }

        return ['modules' => $modules, 'integrations' => ['الذكاء الاصطناعي' => config('clinic-integrations.ai'), 'تحليلات Google' => config('clinic-integrations.google'), 'الإرسال التلقائي لواتساب' => config('clinic-integrations.whatsapp')]];
    }
}
