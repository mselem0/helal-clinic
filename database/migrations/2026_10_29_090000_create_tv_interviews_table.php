<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_interviews', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('youtube_url', 500)->unique();
            $table->string('cover_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        DB::table('page_sections')->insertOrIgnore([
            'section_key' => 'tv-interviews', 'section_label' => 'لقاءات تلفزيونية',
            'order' => 45, 'is_active' => true,
            'body' => json_encode(['fields' => [], 'faqs' => [], 'stages' => []], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([
            ['title' => 'لقاء تلفزيوني 1', 'youtube_url' => 'https://youtu.be/e5T1gvR87wU', 'order' => 1],
            ['title' => 'لقاء تلفزيوني 2', 'youtube_url' => 'https://youtu.be/2HLeCv7_1xw', 'order' => 2],
        ] as $interview) {
            DB::table('tv_interviews')->insertOrIgnore($interview + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        $home = DB::table('site_pages')->where('page_key', 'home')->first(['id', 'sections', 'draft_data']);
        if ($home) {
            $sections = json_decode($home->sections ?: '[]', true) ?: [];
            $insertAfterVideos = static function (array $sections): array {
                $keys = array_map(fn ($item) => is_array($item) ? ($item['key'] ?? '') : $item, $sections);
                if (in_array('tv-interviews', $keys, true)) {
                    return $sections;
                }
                $hasArrayEntries = collect($sections)->contains(fn ($item) => is_array($item));
                $newSection = $hasArrayEntries ? ['key' => 'tv-interviews'] : 'tv-interviews';
                $index = array_search('videos', $keys, true);
                array_splice($sections, $index === false ? count($sections) : $index + 1, 0, [$newSection]);
                return $sections;
            };
            $updates = [];
            $nextSections = $insertAfterVideos($sections);
            if ($nextSections !== $sections) {
                $updates['sections'] = json_encode($nextSections, JSON_UNESCAPED_UNICODE);
                $updates['public_updated_at'] = now();
            }
            $draftData = json_decode($home->draft_data ?? '', true) ?: [];
            if (isset($draftData['sections']) && is_array($draftData['sections'])) {
                $nextDraftSections = $insertAfterVideos($draftData['sections']);
                if ($nextDraftSections !== $draftData['sections']) {
                    $draftData['sections'] = $nextDraftSections;
                    $updates['draft_data'] = json_encode($draftData, JSON_UNESCAPED_UNICODE);
                }
            }
            if ($updates) {
                DB::table('site_pages')->where('id', $home->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        $home = DB::table('site_pages')->where('page_key', 'home')->first(['id', 'sections']);
        if ($home) {
            $sections = json_decode($home->sections ?: '[]', true) ?: [];
            $sections = array_values(array_filter($sections, fn ($item) => (is_array($item) ? ($item['key'] ?? '') : $item) !== 'tv-interviews'));
            DB::table('site_pages')->where('id', $home->id)->update(['sections' => json_encode($sections, JSON_UNESCAPED_UNICODE)]);
        }
        DB::table('page_sections')->where('section_key', 'tv-interviews')->delete();
        Schema::dropIfExists('tv_interviews');
    }
};
