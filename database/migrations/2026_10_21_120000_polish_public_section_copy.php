<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $replacements = [
            'section-5' => [
                'field_7' => [
                    'old' => ['اختاري اليوم والوقت'],
                    'new' => 'اختاري اليوم المناسب',
                ],
                'field_8' => [
                    'old' => ['تقويم بسيط وأوقات واضحة للاختيار.'],
                    'new' => 'حددي يومًا مفضلًا، ويؤكد فريق العيادة الوقت معكِ.',
                ],
                'field_11' => [
                    'old' => ['بيانات مختصرة ثم رسالة نجاح للتجربة.'],
                    'new' => 'سنراجع طلبكِ ونتواصل معكِ لتأكيد الموعد.',
                ],
            ],
            'booking' => [
                'field_4' => [
                    'old' => ['اختاري الخدمة والفرع، ثم اليوم والوقت، وراجعي تفاصيل زيارتكِ في خطوات بسيطة.'],
                    'new' => 'اختاري الخدمة والفرع واليوم المفضل، ثم راجعي بياناتكِ قبل إرسال طلب الموعد.',
                ],
                'field_42' => [
                    'old' => ['اختاري الوقت', 'اختاري الوقت '],
                    'new' => 'تنسيق وقت الزيارة',
                ],
            ],
            'articles' => [
                'field_16' => [
                    'old' => [
                        'محتوى مبدئي للعرض يحتاج مراجعة الدكتور قبل النشر.',
                        'تصفّحي مقالاتنا المعتمدة طبيًا قبل زيارتكِ.',
                    ],
                    'new' => 'للاستعداد لزيارتكِ، دوّني أسئلتكِ لمناقشتها مع الطبيب.',
                ],
            ],
        ];

        foreach ($replacements as $sectionKey => $fields) {
            foreach (DB::table('page_sections')->where('section_key', $sectionKey)->get() as $section) {
                $body = json_decode($section->body ?? '', true);
                if (! is_array($body) || ! is_array($body['fields'] ?? null)) {
                    continue;
                }

                $changed = false;
                foreach ($body['fields'] as &$field) {
                    $replacement = $fields[$field['key'] ?? ''] ?? null;
                    if ($replacement && in_array($field['value'] ?? null, $replacement['old'], true)) {
                        $oldValue = $field['value'];
                        $field['value'] = $replacement['new'];
                        if (($field['label'] ?? null) === $oldValue) {
                            $field['label'] = $replacement['new'];
                        }
                        $changed = true;
                    }
                }
                unset($field);

                if ($changed) {
                    DB::table('page_sections')->where('id', $section->id)->update([
                        'body' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // This content migration is intentionally forward-only: reverting copy could overwrite
        // editorial changes made after deployment or reintroduce known demo text.
    }
};
