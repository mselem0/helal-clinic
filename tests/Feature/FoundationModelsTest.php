<?php

// tests/Feature/FoundationModelsTest.php

namespace Tests\Feature;

use App\Models\PageSection;
use App\Support\ClinicFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\Models\Booking;
use Modules\Whatsapp\Models\WhatsappLog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FoundationModelsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_filter_active_page_sections()
    {
        PageSection::create([
            'section_key' => 'hero',
            'section_label' => 'Hero Test',
            'is_active' => true,
            'order' => 1,
        ]);

        PageSection::create([
            'section_key' => 'about',
            'section_label' => 'About Test',
            'is_active' => false,
            'order' => 2,
        ]);

        $activeSections = PageSection::active()->get();

        $this->assertCount(1, $activeSections);
        $this->assertEquals('hero', $activeSections->first()->section_key);
    }

    #[Test]
    public function it_automatically_logs_whatsapp_interaction_on_booking_creation()
    {
        if (! class_exists(Booking::class)) {
            $this->assertFalse(ClinicFeatures::enabled('Booking'));

            return;
        }
        // 1. Create a booking
        $booking = Booking::create([
            'patient_name' => 'محمد سليم',
            'patient_phone' => '01214584120',
            'booking_date' => '2026-05-20',
            'booking_time' => '18:00:00',
            'source' => 'booking_form',
            'status' => 'new',
        ]);

        // 2. Assert that a WhatsappLog has been auto-created via the Booking booted method
        $whatsappLog = WhatsappLog::where('booking_id', $booking->id)->first();

        $this->assertNotNull($whatsappLog);
        $this->assertEquals('محمد سليم', $whatsappLog->patient_name);
        $this->assertEquals('01214584120', $whatsappLog->patient_phone);
        $this->assertEquals('new', $whatsappLog->status);
    }
}
