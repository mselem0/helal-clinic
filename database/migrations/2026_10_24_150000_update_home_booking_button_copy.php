<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $section = DB::table('page_sections')->where('section_key', 'home')->first();
        if (!$section) return;
        $body = json_decode($section->body ?? '{}', true);
        foreach ($body['fields'] ?? [] as $index => $field) {
            if (($field['key'] ?? '') === 'field_8') {
                $body['fields'][$index]['value'] = 'احجزي ميعادك الآن';
                DB::table('page_sections')->where('id', $section->id)->update(['body' => json_encode($body, JSON_UNESCAPED_UNICODE), 'public_updated_at' => now()]);
                break;
            }
        }
    }

    public function down(): void
    {
        // Preserve subsequent CMS edits.
    }
};
