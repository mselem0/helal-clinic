<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Filament\Pages\ThemeEditor;
use App\Filament\Resources\ArticleResource;
use App\Filament\Resources\ClinicBranchResource;
use App\Filament\Resources\ServiceResource;
use App\Filament\Resources\SitePageResource;
use App\Filament\Resources\TestimonialResource;
use App\Filament\Resources\VideoResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
class SimpleContentHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    public function test_hub_links_to_all_content_areas_and_keeps_publication_controls(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $component = Livewire::test(ThemeEditor::class)
            ->assertSee('محتوى الموقع')
            ->assertSee('حفظ المسودة')
            ->assertSee('معاينة الموقع')
            ->assertSee('نشر الموقع')
            ->assertSee('خيارات إضافية: المظهر والإصدارات')
            ->assertDontSee('محتوى يحتاج مراجعة')
            ->assertDontSee('استوديو موقع هلال');

        foreach ([SiteSettings::class, SitePageResource::class, ServiceResource::class, ClinicBranchResource::class, VideoResource::class, TestimonialResource::class, ArticleResource::class] as $destination) {
            $component->assertSee('href="'.$destination::getUrl().'"', false);
            $this->get($destination::getUrl())->assertOk();
        }
    }

    public function test_hub_only_shows_links_the_editor_can_open(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->givePermissionTo('page_ThemeEditor');
        $this->actingAs($user);

        Livewire::test(ThemeEditor::class)
            ->assertSee('href="'.SitePageResource::getUrl().'"', false)
            ->assertDontSee('href="'.SiteSettings::getUrl().'"', false)
            ->assertDontSee('href="'.ServiceResource::getUrl().'"', false)
            ->assertDontSee('href="'.ArticleResource::getUrl().'"', false);
    }
}
