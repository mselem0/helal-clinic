<?php
namespace Tests\Feature;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
class SimpleAdminNavigationTest extends TestCase
{
 use RefreshDatabase;
 public function test_admin_has_exactly_two_navigation_entries_and_no_dashboard_widgets(): void
 {
  $this->seed(); $user=User::factory()->create(); $user->assignRole('super_admin'); $this->actingAs($user);
  $panel=Filament::getPanel('admin'); Filament::setCurrentPanel($panel); $panel->boot();
  $labels=collect($panel->getNavigation())->flatMap(fn($group)=>$group->getItems())->map(fn($item)=>$item->getLabel())->values()->all();
  $this->assertSame(['محتوى الموقع','طلبات الحجوزات'],$labels);
  $this->assertSame([], $panel->getWidgets());
  $this->assertStringContainsString('/theme-editor',$panel->getHomeUrl());
  $this->get('/codeAiAdminDash/theme-editor')->assertOk()->assertDontSee('إعدادات الربط والتتبع');
 }
}
