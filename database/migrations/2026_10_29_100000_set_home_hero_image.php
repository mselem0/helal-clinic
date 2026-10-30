<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $section = DB::table('page_sections')->where('section_key', 'home')->first();
        if (! $section) {
            return;
        }

        $body = json_decode($section->body ?? '{}', true) ?: [];
        $previousImage = null;
        foreach ($body['fields'] ?? [] as $index => $field) {
            if (($field['key'] ?? '') === 'field_15') {
                $previousImage = $field['value'] ?? null;
                $body['fields'][$index]['value'] = 'assets/hero-doctor.webp';
                break;
            }
        }

        $updates = ['body' => json_encode($body, JSON_UNESCAPED_UNICODE), 'public_updated_at' => now()];
        if ($section->draft_data) {
            $draft = json_decode($section->draft_data, true) ?: [];
            $draftBody = json_decode($draft['body'] ?? '', true);
            if (is_array($draftBody)) {
                foreach ($draftBody['fields'] ?? [] as $index => $field) {
                    if (($field['key'] ?? '') === 'field_15' && ($field['value'] ?? null) === $previousImage) {
                        $draftBody['fields'][$index]['value'] = 'assets/hero-doctor.webp';
                        $draft['body'] = json_encode($draftBody, JSON_UNESCAPED_UNICODE);
                        $updates['draft_data'] = json_encode($draft, JSON_UNESCAPED_UNICODE);
                        break;
                    }
                }
            }
        }

        DB::table('page_sections')->where('id', $section->id)->update($updates);
    }

    public function down(): void
    {
        // Keep the published image and subsequent CMS edits when rolling back.
    }
};
