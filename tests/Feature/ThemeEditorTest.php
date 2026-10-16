<?php

namespace Tests\Feature;

use App\Filament\Pages\ThemeEditor;
use App\Models\PageSection;
use App\Models\User;
use App\Services\ThemeDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ThemeEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $user = User::factory()->create(['role' => 'admin']);
        $user->assignRole('super_admin');
        $this->actingAs($user);
    }

    public function test_editor_loads_and_selects_section(): void
    {
        $this->get(ThemeEditor::getUrl())->assertOk();
        $section = PageSection::where('section_key', 'faq')->first();
        Livewire::test(ThemeEditor::class)->call('selectSection', $section->id)->assertSet('selectedSectionId', $section->id)->assertHasNoErrors();
    }

    public function test_order_and_visibility_are_staged_until_publish(): void
    {
        $section = PageSection::where('section_key', 'faq')->first();
        Livewire::test(ThemeEditor::class)->call('selectSection', $section->id)->fillForm(['order' => 999, 'is_active' => false])->call('save')->assertHasNoErrors();
        $this->assertTrue($section->fresh()->is_active);
        $this->assertSame(999, $section->fresh()->draft_data['order']);
        app(ThemeDraftService::class)->publish();
        $this->assertFalse($section->fresh()->is_active);
        $this->assertEquals(999, $section->fresh()->order);
    }
}
