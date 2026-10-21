<?php

namespace Tests\Feature;

use App\Models\SeoPage;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorSearchIdentityMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_metadata_can_be_updated_within_mysql_column_limits(): void
    {
        $this->seed();
        $migration = require database_path('migrations/2026_10_19_230000_focus_search_identity_on_doctor.php');
        $migration->up();
        $home = SeoPage::where('page_key', 'home')->firstOrFail();
        $this->assertLessThanOrEqual(160, mb_strlen($home->meta_description));
        $this->assertStringStartsWith('د. محمد هلال |', $home->meta_title);
        $this->assertSame($home->meta_description, SitePage::where('page_key', 'home')->value('meta_description'));
        $this->assertStringContainsString('جامعة الأزهر', $home->meta_description);
    }
}
