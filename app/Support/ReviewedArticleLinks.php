<?php

namespace App\Support;

final class ReviewedArticleLinks
{
    /** @return array<string, array{string, string}> */
    public static function services(): array
    {
        return [
            'article-1' => ['أول موعد لمتابعة الحمل: كيف تستعدين؟', 'service-1'],
            'article-2' => ['تقييم تأخر الإنجاب: ما الذي يحدث في البداية؟', 'service-2'],
            'article-3' => ['قبل منظار الرحم أو البطن: أسئلة تساعدكِ على اتخاذ قرار واعٍ', 'service-3'],
            'gestational-diabetes-birth-options' => ['سكر الحمل والولادة: هل يعني التشخيص ولادة قيصرية؟', 'service-1'],
            'c-section-perineal-wound-care' => ['العناية بجرح القيصرية وغرز العجان بعد الولادة', 'service-1'],
            'postpartum-emergency-warning-signs' => ['بعد الولادة: علامات خطر لا تنتظري معها', 'service-1'],
        ];
    }

    /** @return array<string, array{title: string, targets: array<string, string>}> */
    public static function furtherReading(): array
    {
        return [
            'article-1' => [
                'title' => 'أول موعد لمتابعة الحمل: كيف تستعدين؟',
                'targets' => [
                    'gestational-diabetes-birth-options' => 'سكر الحمل والولادة: هل يعني التشخيص ولادة قيصرية؟',
                    'postpartum-emergency-warning-signs' => 'بعد الولادة: علامات خطر لا تنتظري معها',
                ],
            ],
            'gestational-diabetes-birth-options' => [
                'title' => 'سكر الحمل والولادة: هل يعني التشخيص ولادة قيصرية؟',
                'targets' => [
                    'article-1' => 'أول موعد لمتابعة الحمل: كيف تستعدين؟',
                    'postpartum-emergency-warning-signs' => 'بعد الولادة: علامات خطر لا تنتظري معها',
                ],
            ],
            'c-section-perineal-wound-care' => [
                'title' => 'العناية بجرح القيصرية وغرز العجان بعد الولادة',
                'targets' => ['postpartum-emergency-warning-signs' => 'بعد الولادة: علامات خطر لا تنتظري معها'],
            ],
            'postpartum-emergency-warning-signs' => [
                'title' => 'بعد الولادة: علامات خطر لا تنتظري معها',
                'targets' => ['c-section-perineal-wound-care' => 'العناية بجرح القيصرية وغرز العجان بعد الولادة'],
            ],
        ];
    }
}
