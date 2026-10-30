<?php

namespace Tests\Feature;

use App\Filament\Pages\ThemeEditor;
use App\Filament\Resources\SitePageResource\Pages\EditSitePage;
use App\Models\PageSection;
use App\Models\SitePage;
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
        $this->get('/frequently-asked-questions')->assertOk()->assertSee('سؤال جديد؟');
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
        $this->get('/codeAiAdminDash')->assertRedirect('/codeAiAdminDash/website-booking-requests');
        $this->get(ThemeEditor::getUrl())->assertOk();
    }

    public function test_simple_editor_preserves_hidden_values_and_existing_draft_data(): void
    {
        $this->admin();
        $section = PageSection::where('section_key', 'home')->firstOrFail();
        $body = json_decode($section->body, true);
        $body['custom_payload'] = ['keep' => true];
        $body['fields'][] = ['key' => 'private_url', 'type' => 'url', 'label' => 'وجهة الزر', 'value' => '/keep-this'];
        $section->update(['draft_data' => ['body' => json_encode($body), 'order' => 937]]);
        $style = SiteSetting::where('key', 'theme_helal_classic')->first();
        $styleBefore = $style->attributesToArray();
        $component = Livewire::test(ThemeEditor::class)->call('selectSection', $section->id);
        $data = $component->get('data');
        $first = array_key_first($data['fields']);
        $data['fields'][$first]['value'] = 'محتوى مبسط';
        $component->set('data', $data)->call('save')->assertHasNoErrors();
        $saved = json_decode($section->fresh()->draft_data['body'], true);
        $this->assertSame(['keep' => true], $saved['custom_payload']);
        $this->assertSame('/keep-this', collect($saved['fields'])->firstWhere('key', 'private_url')['value']);
        $this->assertSame(937, $section->fresh()->draft_data['order']);
        $this->assertSame($styleBefore, $style->fresh()->attributesToArray());
        $this->assertStringNotContainsString('محتوى مبسط', $section->fresh()->body);
    }

    public function test_simple_page_editor_preserves_hidden_metadata_and_section_order(): void
    {
        $this->admin();
        $page = SitePage::where('page_key', 'home')->firstOrFail();
        $originalSections = $page->sections;
        $page->update(['draft_data' => ['meta_title' => 'بيانات بحث محفوظة', 'canonical_url' => 'https://example.test/keep']]);
        Livewire::test(EditSitePage::class, ['record' => $page->id])
            ->fillForm(['title' => 'الرئيسية المعدلة'])->call('save')->assertHasNoErrors();
        $draft = $page->fresh()->draft_data;
        $this->assertSame('بيانات بحث محفوظة', $draft['meta_title']);
        $this->assertSame('https://example.test/keep', $draft['canonical_url']);
        $keys = fn ($values) => array_map(fn ($value) => is_array($value) ? $value['key'] : $value, $values);
        $this->assertSame($keys($originalSections), $keys($draft['sections']));
        $this->assertNotSame('الرئيسية المعدلة',$page->fresh()->title);
    }
}
