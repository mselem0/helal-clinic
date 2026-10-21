<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicCopyMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_copy_migration_updates_all_known_section_values_and_preserves_custom_copy(): void
    {
        $knownFields = [
            ['key' => 'field_7', 'label' => 'اختاري اليوم والوقت', 'value' => 'اختاري اليوم والوقت'],
            ['key' => 'field_8', 'label' => 'تقويم بسيط وأوقات واضحة للاختيار.', 'value' => 'تقويم بسيط وأوقات واضحة للاختيار.'],
            ['key' => 'field_11', 'label' => 'بيانات مختصرة ثم رسالة نجاح للتجربة.', 'value' => 'بيانات مختصرة ثم رسالة نجاح للتجربة.'],
        ];
        $customFields = [
            ['key' => 'field_7', 'label' => 'اختاري اليوم والوقت', 'value' => 'اختاري اليوم والوقت'],
            ['key' => 'field_8', 'label' => 'تفاصيل مخصصة للفرع', 'value' => 'تفاصيل مخصصة للفرع'],
            ['key' => 'field_11', 'label' => 'بيانات مختصرة ثم رسالة نجاح للتجربة.', 'value' => 'بيانات مختصرة ثم رسالة نجاح للتجربة.'],
        ];

        foreach ([$knownFields, $customFields] as $fields) {
            DB::table('page_sections')->insert([
                'section_key' => 'section-5',
                'section_label' => 'First visit steps',
                'body' => json_encode(['fields' => $fields], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $migration = require database_path('migrations/2026_10_21_120000_polish_public_section_copy.php');
        $migration->up();
        $afterFirstRun = DB::table('page_sections')->where('section_key', 'section-5')->orderBy('id')->get();

        $this->assertCount(2, $afterFirstRun);
        $firstFields = json_decode($afterFirstRun[0]->body, true)['fields'];
        $this->assertSame('اختاري اليوم المناسب', $this->field($firstFields, 'field_7')['value']);
        $this->assertSame('حددي يومًا مفضلًا، ويؤكد فريق العيادة الوقت معكِ.', $this->field($firstFields, 'field_8')['value']);
        $this->assertSame('سنراجع طلبكِ ونتواصل معكِ لتأكيد الموعد.', $this->field($firstFields, 'field_11')['value']);
        $this->assertSame('اختاري اليوم المناسب', $this->field($firstFields, 'field_7')['label']);

        $secondFields = json_decode($afterFirstRun[1]->body, true)['fields'];
        $this->assertSame('اختاري اليوم المناسب', $this->field($secondFields, 'field_7')['value']);
        $this->assertSame('تفاصيل مخصصة للفرع', $this->field($secondFields, 'field_8')['value']);
        $this->assertSame('سنراجع طلبكِ ونتواصل معكِ لتأكيد الموعد.', $this->field($secondFields, 'field_11')['value']);

        $firstRunBodies = $afterFirstRun->pluck('body')->all();
        $migration->up();
        $this->assertSame(
            $firstRunBodies,
            DB::table('page_sections')->where('section_key', 'section-5')->orderBy('id')->pluck('body')->all(),
        );
    }

    public function test_public_copy_migration_down_preserves_all_content(): void
    {
        $body = json_encode([
            'fields' => [
                ['key' => 'field_7', 'label' => 'موعد مخصص', 'value' => 'موعد مخصص'],
                ['key' => 'field_8', 'label' => 'نص المحرر', 'value' => 'نص المحرر'],
                ['key' => 'field_11', 'label' => 'رسالة معدلة', 'value' => 'رسالة معدلة'],
            ],
        ], JSON_UNESCAPED_UNICODE);
        $id = DB::table('page_sections')->insertGetId([
            'section_key' => 'section-5',
            'section_label' => 'First visit steps',
            'body' => $body,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_10_21_120000_polish_public_section_copy.php');
        $migration->down();

        $this->assertSame($body, DB::table('page_sections')->where('id', $id)->value('body'));
    }

    private function field(array $fields, string $key): array
    {
        return collect($fields)->firstWhere('key', $key);
    }
}
