<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $section = DB::table('page_sections')->where('section_key', 'booking')->first();
        if (! $section) {
            return;
        }
        $body = json_decode($section->body ?? '{}', true);
        $changed = false;
        foreach ($body['fields'] ?? [] as $index => $field) {
            if (($field['key'] ?? '') === 'field_9' && ($field['value'] ?? '') === 'الطلب لا يؤكد الموعد؛ يتواصل معكِ فريق العيادة للتأكيد.') {
                $body['fields'][$index]['value'] = 'فريق العيادة هيتواصل معاكي لتأكيد موعدك بعد إرسال الطلب.';
                $changed = true;
            }
        }
        if ($changed) {
            DB::table('page_sections')->where('id', $section->id)->update(['body' => json_encode($body, JSON_UNESCAPED_UNICODE), 'public_updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Keep the editorial text and any later admin edits.
    }
};
