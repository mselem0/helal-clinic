<?php

namespace Tests\Feature;

use App\Models\PageSection;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PatientJourneyWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_embedded_booking_respects_publication_preview_and_avoids_duplicate_forms(): void
    {
        $this->seed();
        $page = SitePage::where('page_key', 'journey')->firstOrFail();
        $page->update(['sections' => ['booking', 'your-journey']]);
        $html = $this->get('/patient-journey')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'id="booking-form"'));
        $this->assertStringContainsString('id="journey-booking-panel"', $html);
        $appointment = SitePage::where('page_key', 'appointment')->firstOrFail();
        $appointment->update(['draft_data' => ['is_published' => false]]);
        $this->get('/patient-journey')->assertSee('id="journey-booking-panel"', false);
        $this->get(URL::temporarySignedRoute('site.preview', now()->addMinutes(5), ['page' => $page->id, 'preview' => 1]))->assertOk()->assertDontSee('id="journey-booking-panel"', false);
        $appointment->update(['is_published' => false]);
        // The standalone booking section is removed from this page to check the integrated action.
        $page->update(['sections' => ['your-journey']]);
        $this->get('/patient-journey')->assertOk()->assertDontSee('id="booking-form"', false)->assertDontSee('id="journey-booking-panel"', false);
        $appointment->update(['is_published' => true]);
        PageSection::where('section_key', 'booking')->update(['is_active' => false]);
        $this->get('/patient-journey')->assertOk()->assertDontSee('id="booking-form"', false);
    }
}
