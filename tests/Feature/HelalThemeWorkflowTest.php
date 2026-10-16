<?php

namespace Tests\Feature;

use App\Filament\Pages\ThemeEditor;
use App\Models\PageSection;
use App\Models\SiteSetting;
use App\Models\ThemeRevision;
use App\Models\User;
use App\Services\ThemeDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class HelalThemeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        return $user;
    }

    public function test_opening_theme_editor_does_not_activate_theme(): void
    {
        $this->admin();
        Livewire::test(ThemeEditor::class)->call('editThemeOnly', 'helal_modern')->assertSet('editingTheme', 'helal_modern')->assertHasNoErrors();
        $this->assertSame('helal_classic', SiteSetting::where('key', 'active_theme')->value('value'));
    }

    public function test_draft_preview_publish_discard_and_restore_preserve_published_content(): void
    {
        $section = PageSection::where('section_key', 'home')->first();
        $before = $section->body;
        $data = json_decode($before, true);
        $data['fields'][0]['value'] = 'عنوان خاص للمسودة';
        $drafts = app(ThemeDraftService::class);
        $drafts->saveSectionDraft('home', ['body' => json_encode($data, JSON_UNESCAPED_UNICODE), 'order' => 900]);
        $this->get('/')->assertOk()->assertDontSee('عنوان خاص للمسودة');
        $this->get(URL::temporarySignedRoute('home', now()->addMinutes(5), ['preview' => 1]))->assertOk()->assertSee('عنوان خاص للمسودة');
        $drafts->discardDrafts();
        $this->assertSame($before, $section->fresh()->body);
        $this->assertDatabaseCount('page_sections', 13);
        $drafts->saveSectionDraft('home', ['body' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
        $drafts->publish();
        $this->get('/')->assertSee('عنوان خاص للمسودة');
        $id = ThemeRevision::first()->id;
        $drafts->restoreRevision($id);
        $this->assertSame($before, $section->fresh()->body);
        $this->assertDatabaseCount('theme_revisions', 2);
    }

    public function test_editor_save_persists_actual_form_values_as_draft(): void
    {
        $this->admin();
        $section = PageSection::where('section_key', 'faq')->first();
        $component = Livewire::test(ThemeEditor::class)->call('selectSection', $section->id);
        $data = $component->get('data');
        $first = array_key_first($data['fields']);
        $data['fields'][$first]['value'] = 'أسئلة جديدة';
        $component->set('data', $data)->call('save')->assertHasNoErrors();
        $this->assertStringContainsString('أسئلة جديدة', $section->fresh()->draft_data['body']);
        $this->assertStringNotContainsString('أسئلة جديدة', $section->fresh()->body);
    }

    public function test_faq_repeater_can_publish_new_questions(): void
    {
        $this->admin();
        $section = PageSection::where('section_key', 'faq')->first();
        Livewire::test(ThemeEditor::class)->call('selectSection', $section->id)->fillForm(['faqs' => [['question' => 'سؤال جديد؟', 'answer' => 'إجابة جديدة.']]])->call('publish')->assertHasNoErrors();
        $this->get('/')->assertOk()->assertSee('سؤال جديد؟');
    }

    public function test_guest_and_non_admin_cannot_open_editor(): void
    {
        $this->get(ThemeEditor::getUrl())->assertRedirect();
        $user = User::factory()->create();
        $this->actingAs($user)->get(ThemeEditor::getUrl())->assertForbidden();
    }

    public function test_admin_dashboard_renders_with_optional_modules_absent(): void
    {
        $this->admin();
        $this->get('/codeAiAdminDash')->assertOk();
        $this->get(ThemeEditor::getUrl())->assertOk();
    }
}
