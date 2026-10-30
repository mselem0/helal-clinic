<?php

namespace Tests\Feature;

use App\Filament\Resources\TvInterviewResource;
use App\Filament\Resources\TvInterviewResource\Pages\CreateTvInterview;
use App\Filament\Resources\TvInterviewResource\Pages\EditTvInterview;
use App\Filament\Resources\TvInterviewResource\Pages\ListTvInterviews;
use App\Models\TvInterview;
use App\Models\ThemeRevision;
use App\Models\User;
use App\Models\SitePage;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use App\Services\ThemeDraftService;
use Tests\TestCase;

class TvInterviewSectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_interviews_appear_on_home_without_eagerly_embedding_them(): void
    {
        $response = $this->get('/')->assertOk()->assertSee('لقاء تلفزيوني 1')->assertSee('data-tv-interview-id="e5T1gvR87wU"', false);
        $response->assertDontSee('<iframe', false)->assertDontSee('youtube-nocookie.com/embed', false);
        $this->get('/gallery')->assertOk()->assertDontSee('data-tv-interview-id=', false);
    }

    public function test_inactive_interviews_are_hidden_from_public_pages(): void
    {
        TvInterview::where('youtube_url', 'https://youtu.be/e5T1gvR87wU')->update(['is_active' => false]);

        $this->get('/')->assertOk()->assertDontSee('لقاء تلفزيوني 1')->assertSee('لقاء تلفزيوني 2');
    }

    public function test_inactive_tv_section_respects_cms_visibility(): void
    {
        PageSection::where('section_key', 'tv-interviews')->update(['is_active' => false]);

        $this->get('/')->assertOk()->assertDontSee('data-tv-interview-id=', false);
    }

    public function test_content_manager_can_open_admin_crud_but_regular_users_cannot(): void
    {
        $user = User::factory()->create();
        $user->assignRole('content_manager');
        $this->actingAs($user);

        $this->get(TvInterviewResource::getUrl())->assertOk()->assertSee('اللقاءات التلفزيونية');

        Livewire::test(CreateTvInterview::class)
            ->fillForm(['title' => 'رابط غير صالح', 'youtube_url' => 'https://youtube.com.evil.test/watch?v=e5T1gvR87wU', 'is_active' => true])
            ->call('create')
            ->assertHasFormErrors(['youtube_url']);

        Livewire::test(CreateTvInterview::class)
            ->fillForm([
                'title' => 'لقاء تجريبي',
                'description' => 'وصف للاختبار',
                'youtube_url' => 'https://www.youtube.com/watch?v=e5T1gvR87wU&list=PLtest',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        $interview = TvInterview::where('title', 'لقاء تجريبي')->firstOrFail();

        Livewire::test(EditTvInterview::class, ['record' => $interview->getKey()])
            ->fillForm(['title' => 'لقاء معدّل'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('لقاء معدّل', $interview->fresh()->title);

        $orderedIds = [$interview->id, TvInterview::where('youtube_url', 'https://youtu.be/2HLeCv7_1xw')->value('id'), TvInterview::where('youtube_url', 'https://youtu.be/e5T1gvR87wU')->value('id')];
        Livewire::test(ListTvInterviews::class)->call('reorderTable', $orderedIds);
        $this->assertSame($orderedIds, TvInterview::orderBy('order')->pluck('id')->all());

        auth()->logout();
        $regularUser = User::factory()->create();
        $this->actingAs($regularUser)->get(TvInterviewResource::getUrl())->assertForbidden();
    }

    public function test_targeted_installer_is_idempotent_and_preserves_custom_section_order_and_drafts(): void
    {
        $page = SitePage::where('page_key', 'home')->firstOrFail();
        $customSections = [['key' => 'home', 'custom' => 'kept'], ['key' => 'videos', 'position' => 'first'], 'faq', ['key' => 'booking', 'custom' => 'kept']];
        $customDraftSections = [['key' => 'home', 'draft' => 'kept'], ['key' => 'videos', 'draftPosition' => 'kept'], 'faq', ['key' => 'booking', 'draft' => 'kept']];
        $page->update(['sections' => $customSections, 'draft_data' => ['sections' => $customDraftSections, 'title' => 'Draft']]);

        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\TvInterviewInstallerSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\TvInterviewInstallerSeeder', '--force' => true]);

        $page->refresh();
        $this->assertSame([
            ['key' => 'home', 'custom' => 'kept'], ['key' => 'videos', 'position' => 'first'],
            ['key' => 'tv-interviews'], 'faq', ['key' => 'booking', 'custom' => 'kept'],
        ], $page->sections);
        $expectedDraftSections = [['key' => 'home', 'draft' => 'kept'], ['key' => 'videos', 'draftPosition' => 'kept'], ['key' => 'tv-interviews'], 'faq', ['key' => 'booking', 'draft' => 'kept']];
        $this->assertSame(['sections' => $expectedDraftSections, 'title' => 'Draft'], $page->draft_data);
        $this->assertSame(2, TvInterview::count());

        app(\App\Services\ThemeDraftService::class)->publish();
        $page->refresh();
        $this->assertSame($expectedDraftSections, $page->sections);
        $this->assertNull($page->draft_data);
    }

    public function test_gallery_does_not_get_tv_interviews_as_a_default_section(): void
    {
        $this->assertSame(['videos'], SitePage::where('page_key', 'videos')->firstOrFail()->sections);
    }

    public function test_initial_interview_ids_and_home_image_assets_exist(): void
    {
        $this->assertSame(['e5T1gvR87wU', '2HLeCv7_1xw'], TvInterview::orderBy('order')->get()->map->youtube_id->all());
        foreach (['hero-doctor.webp', 'hero-doctor-320.webp', 'hero-doctor-640.webp', 'hero-doctor-960.webp'] as $image) {
            $this->assertFileExists(public_path('assets/'.$image));
        }
    }

    public function test_hero_migration_updates_matching_pending_image_but_preserves_a_custom_pending_image(): void
    {
        $section = PageSection::where('section_key', 'home')->firstOrFail();
        $body = json_decode($section->body, true);
        foreach ($body['fields'] as &$field) {
            if ($field['key'] === 'field_15') $field['value'] = 'assets/portrait-clinic.webp';
        }
        unset($field);
        $section->body = json_encode($body, JSON_UNESCAPED_UNICODE);
        $draftBody = $body;
        $section->draft_data = ['body' => json_encode($draftBody, JSON_UNESCAPED_UNICODE), 'is_active' => true];
        $section->save();

        $migration = require database_path('migrations/2026_10_29_100000_set_home_hero_image.php');
        $migration->up();
        $section->refresh();
        $this->assertSame('assets/hero-doctor.webp', $this->fieldValue($section->body, 'field_15'));
        $this->assertSame('assets/hero-doctor.webp', $this->fieldValue($section->draft_data['body'], 'field_15'));

        $body = json_decode($section->body, true);
        $draftBody = json_decode($section->draft_data['body'], true);
        foreach ($draftBody['fields'] as &$field) {
            if ($field['key'] === 'field_15') $field['value'] = 'assets/editor-selected.webp';
        }
        unset($field);
        $section->body = json_encode($body, JSON_UNESCAPED_UNICODE);
        $section->draft_data = ['body' => json_encode($draftBody, JSON_UNESCAPED_UNICODE), 'is_active' => true];
        $section->save();

        $migration->up();
        $section->refresh();
        $this->assertSame('assets/hero-doctor.webp', $this->fieldValue($section->body, 'field_15'));
        $this->assertSame('assets/editor-selected.webp', $this->fieldValue($section->draft_data['body'], 'field_15'));
    }

    public function test_theme_revision_snapshots_and_restores_tv_interview_content(): void
    {
        $service = app(ThemeDraftService::class);
        $snapshot = $service->snapshot();
        $this->assertArrayHasKey(TvInterview::class, $snapshot['content']);
        $original = TvInterview::orderBy('order')->pluck('title', 'id')->all();
        $revision = ThemeRevision::create(['content_snapshot' => $snapshot, 'summary' => 'قبل تعديل اللقاءات']);

        TvInterview::query()->delete();
        TvInterview::create(['title' => 'تعديل تجريبي', 'youtube_url' => 'https://youtu.be/abcdEfGhI12', 'is_active' => true, 'order' => 9]);

        $service->restoreRevision($revision->id);
        $this->assertSame($original, TvInterview::where('is_active', true)->orderBy('order')->pluck('title', 'id')->all());
        $this->assertFalse(TvInterview::where('title', 'تعديل تجريبي')->firstOrFail()->is_active);
    }

    private function fieldValue(string $body, string $key): ?string
    {
        return collect(json_decode($body, true)['fields'] ?? [])->firstWhere('key', $key)['value'] ?? null;
    }
}
