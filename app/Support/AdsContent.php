<?php

namespace App\Support;

use App\Models\Article;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\SitePage;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Public-only projection for the dedicated ads branch. Never writes CMS data. */
final class AdsContent
{
    public const HERO_REEL = '1122351437148430';
    public const HERO_TITLE = 'سكر الحمل: طبيعي ولا قيصري؟';
    private const TOPICS = '~الحقن\s*المجهري|العقم|عقم|ت[أا]خر\s*(?:ال[إا]نجاب|الحمل)|خصوبة|أطفال\s*الأنابيب|اطفال\s*الانابيب|(?:services/)?service-2(?:[/?#]|$)|(?:blog/)?article-2(?:[/?#]|$)|hero-pregnancy-ultrasound|\b(?:ivf|icsi|infertility|fertility)\b~iu';

    public static function contains(mixed $value): bool
    {
        if (is_array($value)) {
            return collect($value)->contains(fn ($part) => self::contains($part));
        }
        if (! is_string($value)) {
            return false;
        }
        // Match rendered entities, escaped JSON, Arabic diacritics and common spelling variants.
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return self::contains($decoded);
        }
        $value = html_entity_decode(rawurldecode($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{200B}-\x{200F}]/u', '', strip_tags($value));

        return (bool) preg_match(self::TOPICS, $value);
    }

    public static function allowed(Model $model): bool
    {
        if ($model instanceof Service && $model->slug === 'service-2') {
            return false;
        }
        if ($model instanceof Article && $model->slug === 'article-2') {
            return false;
        }
        if ($model instanceof Video && str_contains((string) ($model->external_video_url ?: $model->video_path), 'hero-pregnancy-ultrasound')) {
            return false;
        }
        $fields = $model instanceof SitePage
            ? ['title', 'slug', 'intro', 'body']
            : ['name', 'title', 'slug', 'card_title', 'description', 'rich_description', 'content', 'category', 'meta_title', 'meta_description', 'sources', 'review_text', 'patient_name', 'patient_initial', 'address', 'directions', 'working_hours', 'image_path', 'video_path', 'external_video_url'];
        // Standard pages share general copy which is projected below; custom topic pages are hidden.
        if ($model instanceof SitePage && in_array($model->page_key, ['home', 'about', 'services', 'articles', 'branches', 'contact', 'appointment', 'journey', 'faq', 'videos', 'gallery'], true)) {
            return true;
        }

        return ! self::contains($model->only($fields));
    }

    /** Apply before pagination/limits, including direct detail and booking queries. */
    public static function query(Builder $query): Builder
    {
        $excluded = (clone $query)->reorder()->get()->reject(fn ($item) => self::allowed($item))->modelKeys();

        return $query->whereNotIn($query->getModel()->getQualifiedKeyName(), $excluded);
    }

    public static function clean(mixed $value): mixed
    {
        if (is_array($value)) {
            $list = array_is_list($value);
            $result = [];
            foreach ($value as $key => $part) {
                // Drop complete topic cards/FAQs/credentials/stages, rather than relabel them.
                if (is_array($part) && ! array_is_list($part) && self::contains($part) && ! isset($part['key']) && (isset($part['title']) || isset($part['question']) || isset($part['tag']))) {
                    continue;
                }
                $result[$key] = self::clean($part);
            }

            return $list ? array_values($result) : $result;
        }
        if (! is_string($value) || ! self::contains($value)) {
            return $value;
        }
        $json = json_decode($value, true);
        if (is_array($json)) {
            return json_encode(self::clean($json), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        // Shared doctor/clinic descriptions retain their actual remaining specialties.
        $value = str_replace([
            'إلى تأخر الإنجاب والمناظير الجراحية',
            'والمناظير الجراحية والحقن المجهري',
            'والمناظير والحقن المجهري',
            'والحقن المجهري',
            'و الحقن المجهري',
        ], [
            'إلى المناظير الجراحية',
            'والمناظير الجراحية',
            'والمناظير',
            '',
            '',
        ], $value);

        return self::contains($value) ? '' : trim($value);
    }

    public static function model(?Model $model): ?Model
    {
        if (! $model) {
            return null;
        }
        $copy = clone $model;
        foreach (['title', 'intro', 'body', 'meta_title', 'meta_description', 'og_title', 'og_description', 'doctor_profile'] as $field) {
            if (array_key_exists($field, $copy->getAttributes())) {
                $copy->setAttribute($field, self::clean($copy->getAttribute($field)));
            }
        }
        if ($copy instanceof PageSection && $copy->section_key === 'home') {
            $body = json_decode($copy->body ?? '{}', true);
            $overrides = [
                'field_14' => self::HERO_REEL,
                'field_20' => self::HERO_TITLE,
                'field_21' => 'د. محمد هلال يوضح تأثير سكر الحمل على اختيار طريقة الولادة.',
                'field_12' => 'رعاية متخصصة',
                'field_13' => 'لصحة المرأة',
            ];
            foreach ($body['fields'] ?? [] as $index => $field) {
                if (isset($overrides[$field['key'] ?? ''])) {
                    $body['fields'][$index]['value'] = $overrides[$field['key']];
                }
            }
            $copy->body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $copy;
    }
}
