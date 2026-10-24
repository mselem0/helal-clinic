<?php

namespace Tests\Feature;

use App\Helpers\SiteSettingHelper;
use App\Models\ClinicBranch;
use App\Models\Service;
use App\Support\BranchSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClinicContactRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_branch_booking_uses_its_own_confirmed_whatsapp_phone(): void
    {
        Http::preventStrayRequests();
        $this->seed();
        foreach (['السادس من أكتوبر' => '201509011313', 'منشأة القناطر' => '201093239964', 'المنصورية' => '201017598675'] as $name => $phone) {
            $branch = ClinicBranch::where('name', $name)->firstOrFail();
            $day = now('Africa/Cairo')->addDay();
            while (! BranchSchedule::forDate($branch, $day->toDateString())['available']) {
                $day->addDay();
            }
            $this->postJson('/bookings', [
                'patient_name' => 'اختبار توجيه', 'patient_phone' => '01012345678',
                'booking_date' => $day->toDateString(), 'service_id' => Service::first()->id,
                'clinic_branch_id' => $branch->id,
            ])->assertOk()->assertJsonPath('whatsapp_url', fn ($url) => str_starts_with($url, 'https://wa.me/'.$phone.'?text='));
        }
        $this->assertSame('201509011313', SiteSettingHelper::get('wa_number'));
        $this->assertSame('01017900354', SiteSettingHelper::get('doctor_phone'));
    }

    public function test_confirmed_update_preserves_unrelated_branch_data_and_remains_editable(): void
    {
        $this->seed();
        $branch = ClinicBranch::where('name', 'منشأة القناطر')->firstOrFail();
        $branch->update(['address' => 'عنوان خاص', 'phone' => '01000000000', 'schedule_exceptions' => [['date' => '2026-12-25', 'closed' => true]]]);
        $migration = require database_path('migrations/2026_10_24_090000_confirm_branch_contacts_and_hours.php');
        $migration->up();
        $branch->refresh();
        $this->assertSame('01093239964', $branch->phone);
        $this->assertSame('عنوان خاص', $branch->address);
        $this->assertTrue($branch->schedule_exceptions[0]['closed']);
        $this->assertSame(['available' => true, 'from' => '16:00', 'to' => '00:00'], BranchSchedule::forDate($branch, '2026-10-24'));
        $this->assertFalse(BranchSchedule::forDate($branch, '2026-10-25')['available']);
        $branch->update(['phone' => '01011111111']);
        $this->assertSame('01011111111', $branch->fresh()->phone);
    }

    public function test_contact_and_branch_pages_label_booking_and_doctor_contacts_separately(): void
    {
        $this->seed();
        $this->get('/contact-us')->assertOk()->assertSee('01509011313')->assertSee('01093239964')->assertSee('01017598675')->assertSee('للتواصل مع الطبيب')->assertSee('01017900354')->assertSee('https://wa.me/201509011313', false);
        $branch = ClinicBranch::where('name', 'منشأة القناطر')->firstOrFail();
        $this->get('/branches/'.$branch->slug)->assertOk()->assertSee('12 منتصف الليل')->assertSee('01093239964');
    }
}
