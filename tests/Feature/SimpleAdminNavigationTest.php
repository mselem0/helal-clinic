<?php

namespace Tests\Feature;

use App\Models\SitePage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SimpleAdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function navigation(User $user): array
    {
        $this->actingAs($user);
        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $panel->boot();

        return $panel->getNavigation();
    }

    public function test_admin_has_direct_content_areas_and_one_collapsible_pages_menu(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $groups = collect($this->navigation($user));
        $labels = $groups->flatMap(fn ($g) => $g->getItems())->map(fn ($i) => $i->getLabel());
        foreach (['الحجوزات', 'الخدمات', 'المقالات', 'الفروع', 'الفيديوهات', 'آراء المريضات', 'بيانات التواصل'] as $label) {
            $this->assertContains($label, $labels);
        }
        $pages = $groups->first(fn ($g) => $g->getLabel() === 'صفحات الموقع');
        $this->assertNotNull($pages);
        $this->assertTrue($pages->isCollapsible());
        $this->assertTrue($pages->isCollapsed());
        foreach (SitePage::all() as $page) {
            $this->assertContains($page->title, array_map(fn ($i) => $i->getLabel(), $pages->getItems()));
        }
        $this->assertSame([], Filament::getPanel('admin')->getWidgets());
    }

    public function test_content_only_editor_does_not_receive_resource_or_booking_links(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo('page_ThemeEditor');
        $labels = collect($this->navigation($user))->flatMap(fn ($g) => $g->getItems())->map(fn ($i) => $i->getLabel());
        $this->assertNotContains('الخدمات', $labels);
        $this->assertNotContains('الحجوزات', $labels);
        $this->assertContains('كل الصفحات', $labels);
    }
}
