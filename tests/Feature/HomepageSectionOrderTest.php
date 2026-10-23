<?php

namespace Tests\Feature;

use App\Models\PageSection;
use App\Models\SitePage;
use Database\Seeders\MultipageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageSectionOrderTest extends TestCase
{
    use RefreshDatabase;

    private const DEFAULT_ORDER = ['home', 'services', 'about', 'your-journey', 'videos', 'reviews', 'section-5', 'clinics', 'articles', 'faq', 'booking'];

    private const OLD_ORDER = ['home', 'your-journey', 'services', 'about', 'videos', 'section-5', 'clinics', 'articles', 'reviews', 'booking', 'faq'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_fresh_seed_uses_the_curated_homepage_section_order_and_appends_custom_sections(): void
    {
        $page = SitePage::where('page_key', 'home')->firstOrFail();
        $this->assertSame(self::DEFAULT_ORDER, $page->sections);

        PageSection::create(['section_key' => 'custom-promo', 'section_label' => 'مخصص', 'order' => 115, 'is_active' => true]);
        $page->delete();
        $this->seed(MultipageSeeder::class);

        $this->assertSame([...self::DEFAULT_ORDER, 'custom-promo'], SitePage::where('page_key', 'home')->firstOrFail()->sections);
    }

    public function test_migration_updates_only_exact_home_defaults_and_preserves_other_data(): void
    {
        $page = SitePage::where('page_key', 'home')->firstOrFail();
        $publishedAt = $page->published_at;
        $page->update([
            'sections' => self::OLD_ORDER,
            'draft_data' => ['title' => 'مراجعة قائمة الأقسام', 'sections' => self::OLD_ORDER],
        ]);
        $customPage = SitePage::create([
            'page_key' => 'custom-order', 'slug' => 'custom-order', 'title' => 'صفحة مخصصة',
            'sections' => self::OLD_ORDER, 'is_published' => false,
        ]);

        $migration = require database_path('migrations/2026_10_22_160000_refine_default_home_section_order.php');
        $migration->up();

        $page->refresh();
        $this->assertSame(self::DEFAULT_ORDER, $page->sections);
        $this->assertSame(self::DEFAULT_ORDER, $page->draft_data['sections']);
        $this->assertSame('مراجعة قائمة الأقسام', $page->draft_data['title']);
        $this->assertTrue($page->is_published);
        $this->assertEquals($publishedAt, $page->published_at);
        $this->assertSame(self::OLD_ORDER, $customPage->fresh()->sections);

        $customOrder = ['home', 'custom-promo', ...self::DEFAULT_ORDER];
        $page->update(['sections' => $customOrder]);
        $migration->up();
        $this->assertSame($customOrder, $page->fresh()->sections);
        $migration->down();

        $page->refresh();
        $this->assertSame($customOrder, $page->sections);
        $this->assertSame(self::OLD_ORDER, $page->draft_data['sections']);
        $this->assertSame(self::OLD_ORDER, $customPage->fresh()->sections);
    }
}
