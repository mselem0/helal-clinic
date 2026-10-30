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
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SimpleContentHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    public function test_editor_keeps_publication_controls_without_design_or_technical_controls(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->assignRole('super_admin');
        $this->actingAs($user);
        Livewire::test(ThemeEditor::class)->assertSee('حفظ المسودة')->assertSee('معاينة الموقع')->assertSee('نشر الموقع')
            ->assertDontSee('ألوان الموقع')->assertDontSee('خيارات إضافية: المظهر والإصدارات')->assertDontSee('مسار الصورة أو الفيديو')->assertDontSee('الترتيب الافتراضي للقسم');
        foreach ([SiteSettings::class, SitePageResource::class, ServiceResource::class, ClinicBranchResource::class, VideoResource::class, TestimonialResource::class, ArticleResource::class] as $destination) {
            $this->get($destination::getUrl())->assertOk();
        }
    }

    public function test_editor_deep_links_to_requested_page(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('page_ThemeEditor');
        $this->actingAs($user);
        Livewire::withQueryParams(['selectedPageKey' => 'faq'])->test(ThemeEditor::class)->assertSet('selectedPageKey', 'faq')->assertSee('الأسئلة الشائعة');
    }
}
