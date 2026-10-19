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
        $this->assertSame('Person', $doctor['@type']);
        $this->assertSame('صفة مؤكدة', $doctor['jobTitle']);
        $this->assertSame('مؤهل مؤكد', $doctor['hasCredential'][0]['name']);
        $this->assertSame('جمعية مؤكدة', $doctor['memberOf'][0]['name']);
        $this->assertArrayNotHasKey('aggregateRating', $doctor);
    }

    public function test_official_profiles_are_visible_and_draft_links_are_not_exposed(): void
    {
        $this->seed();
        $page = SitePage::where('page_key', 'about')->firstOrFail();
        $page->update(['doctor_profile' => ['official_links' => [
            ['label' => 'Facebook', 'url' => 'https://www.facebook.com/HelalClinic0/'],
            ['label' => 'Unsafe', 'url' => 'javascript:alert(1)'],
        ]], 'draft_data' => ['doctor_profile' => ['official_links' => [['label' => 'Draft', 'url' => 'https://example.com/private-draft']]]]]);
        foreach (['/', '/about-us'] as $path) {
            $html = $this->get($path)->assertOk()->assertDontSee('https://example.com/private-draft')->getContent();
            preg_match('~<script type="application/ld\\+json">(.*?)</script>~s', $html, $matches);
            $doctor = collect(json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR)['@graph'])->first(fn ($node) => ($node['@id'] ?? '') === url('/').'#doctor');
            $this->assertSame(['https://www.facebook.com/HelalClinic0/'], $doctor['sameAs']);
        }
        $this->get('/about-us')->assertSee('href="https://www.facebook.com/HelalClinic0/"', false)->assertDontSee('javascript:alert');
    }

    public function test_search_identity_uses_admin_site_name_and_square_default_icons(): void
    {
        $this->seed();
        \App\Helpers\SeoHelper::setSetting('site_name', 'عيادات هلال الرسمية');
        $html = $this->get('/')->assertOk()->assertSee('assets/favicon-96.png')->assertSee('sizes="144x144"', false)->getContent();
        preg_match('~<script type="application/ld\\+json">(.*?)</script>~s', $html, $matches);
        $site = collect(json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR)['@graph'])->firstWhere('@type', 'WebSite');
        $this->assertSame('عيادات هلال الرسمية', $site['name']);
        foreach ([96, 144] as $size) {
            $dimensions = getimagesize(public_path('assets/favicon-'.$size.'.png'));
            $this->assertSame([$size, $size], array_slice($dimensions, 0, 2));
        }
        \App\Helpers\SeoHelper::setSetting('site_favicon', 'seo/custom-icon.png');
        $this->get('/')->assertOk()->assertSee('/storage/seo/custom-icon.png')->assertDontSee('assets/favicon-96.png');
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
