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
        $body = json_decode($section->body ?? '{}', true, flags: JSON_THROW_ON_ERROR);
        $values = [
            'field_14' => 'assets/hero-pregnancy-ultrasound.mp4',
            'field_19' => 'من داخل العيادة',
            'field_20' => 'متابعة الحمل بالسونار',
            'field_21' => 'لحظات من متابعة الحمل مع د. محمد هلال.',
        ];
        foreach ($body['fields'] ?? [] as $index => $field) {
            if (isset($values[$field['key'] ?? ''])) {
                $body['fields'][$index]['value'] = $values[$field['key']];
            }
        }
        DB::table('page_sections')->where('id', $section->id)->update([
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
            'public_updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Preserve subsequent CMS edits and the existing thumbnail.
    }
};
