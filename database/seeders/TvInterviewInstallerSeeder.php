<?php

namespace Database\Seeders;

use App\Models\PageSection;
use App\Models\SitePage;
use App\Models\TvInterview;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Targeted, repeatable installer for the independent television interview library.
 * Run with: php artisan db:seed --class=TvInterviewInstallerSeeder
 */
class TvInterviewInstallerSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            PageSection::firstOrCreate(['section_key' => 'tv-interviews'], [
                'section_label' => 'لقاءات تلفزيونية',
                'order' => 45,
                'is_active' => true,
                'body' => json_encode(['fields' => [], 'faqs' => [], 'stages' => []], JSON_UNESCAPED_UNICODE),
            ]);

            foreach ([
                ['title' => 'لقاء تلفزيوني 1', 'youtube_url' => 'https://youtu.be/e5T1gvR87wU', 'order' => 1],
                ['title' => 'لقاء تلفزيوني 2', 'youtube_url' => 'https://youtu.be/2HLeCv7_1xw', 'order' => 2],
            ] as $interview) {
                TvInterview::firstOrCreate(['youtube_url' => $interview['youtube_url']], $interview + ['is_active' => true]);
            }

            $home = SitePage::where('page_key', 'home')->first();
            if ($home) {
                $publishedSections = $home->sections ?? [];
                $draftData = $home->draft_data ?? [];
                $draftSections = $draftData['sections'] ?? null;
                $nextPublishedSections = $this->insertAfterVideos($publishedSections);
                $nextDraftSections = is_array($draftSections) ? $this->insertAfterVideos($draftSections) : $draftSections;

                if ($nextPublishedSections !== $publishedSections || $nextDraftSections !== $draftSections) {
                    $home->sections = $nextPublishedSections;
                    if ($nextDraftSections !== $draftSections) {
                        $draftData['sections'] = $nextDraftSections;
                        $home->draft_data = $draftData;
                    }
                    $home->save();
                }
            }

            $permissions = ['view_any_video', 'view_video', 'create_video', 'update_video', 'delete_video'];
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }
            $contentManager = Role::firstOrCreate(['name' => 'content_manager', 'guard_name' => 'web']);
            $contentManager->givePermissionTo($permissions);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    private function insertAfterVideos(array $sections): array
    {
        $keys = array_map(fn ($section) => is_array($section) ? ($section['key'] ?? '') : $section, $sections);
        if (in_array('tv-interviews', $keys, true)) {
            return $sections;
        }

        $hasArrayEntries = collect($sections)->contains(fn ($section) => is_array($section));
        $newSection = $hasArrayEntries ? ['key' => 'tv-interviews'] : 'tv-interviews';
        $videosIndex = array_search('videos', $keys, true);
        array_splice($sections, $videosIndex === false ? count($sections) : $videosIndex + 1, 0, [$newSection]);

        return $sections;
    }
}
