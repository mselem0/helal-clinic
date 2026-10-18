<?php

namespace Tests\Feature;

use App\Models\ClinicBranch;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DoctorBranchSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_credentials_match_published_profile_and_drafts_stay_private(): void
    {
        Http::preventStrayRequests();
        $this->seed();
        $page = SitePage::where('page_key', 'about')->firstOrFail();
        $page->update(['doctor_profile' => ['headline' => 'صفة مؤكدة', 'qualifications' => [['title' => 'مؤهل مؤكد', 'detail' => 'جامعة مؤكدة']], 'memberships' => [['title' => 'جمعية مؤكدة']]], 'draft_data' => ['doctor_profile' => ['headline' => 'صفة مسودة سرية']]]);
        $html = $this->get('/about-us')->assertOk()->assertDontSee('صفة مسودة سرية')->getContent();
        preg_match('~<script type="application/ld\\+json">(.*?)</script>~s', $html, $matches);
        $graph = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR)['@graph'];
        $doctor = collect($graph)->first(fn ($node) => ($node['@id'] ?? '') === url('/').'#doctor');
        $this->assertSame('صفة مؤكدة', $doctor['jobTitle']);
        $this->assertSame('مؤهل مؤكد', $doctor['hasCredential'][0]['name']);
        $this->assertSame('جمعية مؤكدة', $doctor['memberOf'][0]['name']);
        $this->assertArrayNotHasKey('aggregateRating', $doctor);
    }

    public function test_branch_schema_omits_missing_fields_and_invented_hours_or_ratings(): void
    {
        $this->seed();
        $branch = ClinicBranch::firstOrFail();
        $branch->update(['phone' => null, 'address' => '', 'google_maps_url' => null]);
        $html = $this->get($branch->publicPath())->assertOk()->getContent();
        preg_match('~<script type="application/ld\\+json">(.*?)</script>~s', $html, $matches);
        $clinic = collect(json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR)['@graph'])->firstWhere('@type', 'MedicalClinic');
        foreach (['address', 'telephone', 'hasMap', 'aggregateRating', 'openingHours', 'geo'] as $field) {
            $this->assertArrayNotHasKey($field, $clinic);
        }
        $this->assertSame(url($branch->publicPath()), $clinic['url']);
    }
}
