<?php

use App\Helpers\SiteSettingHelper;
use App\Models\ClinicBranch;
use App\Models\HeroSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $confirmed = json_decode(file_get_contents(database_path('content/clinic-contact-update-2026-10-24.json')), true, flags: JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($confirmed): void {
            foreach ($confirmed['branches'] as $data) {
                $branch = ClinicBranch::where('name', $data['name'])->first();
                if ($branch) {
                    $branch->update([
                        'phone' => $data['phone'],
                        'working_hours' => $data['working_hours'],
                        'booking_schedule_enabled' => true,
                        'weekly_schedule' => $data['weekly_schedule'],
                    ]);
                }
            }
            SiteSettingHelper::set('wa_number', $confirmed['wa_number'], 'whatsapp');
            SiteSettingHelper::set('doctor_phone', $confirmed['doctor_phone'], 'contact');
            HeroSetting::query()->update(['whatsapp_number' => $confirmed['wa_number']]);
        });
    }

    public function down(): void
    {
        // Retain confirmed clinic contacts and subsequent administrative edits.
    }
};
