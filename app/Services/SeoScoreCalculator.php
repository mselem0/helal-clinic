<?php

// app/Services/SeoScoreCalculator.php

namespace App\Services;

use App\Models\SeoPage;

class SeoScoreCalculator
{
    protected array $checks = [
        'has_meta_title' => 15,
        'meta_title_good_length' => 10,
        'meta_title_has_keyword' => 10,
        'has_meta_description' => 15,
        'meta_description_good_length' => 10,
        'meta_description_has_keyword' => 5,
        'has_og_image' => 10,
        'has_focus_keywords' => 10,
        'is_indexable' => 10,
        'has_schema' => 5,
    ];

    public function calculate(SeoPage $page): array
    {
        $score = 0;
        $breakdown = [];

        foreach ($this->checks as $check => $points) {
            $passed = $this->{$check}($page);
            if ($passed) {
                $score += $points;
            }
            $breakdown[$check] = ['passed' => $passed, 'points' => $points];
        }

        return ['score' => $score, 'breakdown' => $breakdown];
    }

    protected function has_meta_title(SeoPage $page): bool
    {
        return ! empty($page->meta_title);
    }

    protected function meta_title_good_length(SeoPage $page): bool
    {
        $len = mb_strlen($page->meta_title ?? '');

        return $len >= 30 && $len <= 60;
    }

    protected function has_meta_description(SeoPage $page): bool
    {
        return ! empty($page->meta_description);
    }

    protected function meta_description_good_length(SeoPage $page): bool
    {
        $len = mb_strlen($page->meta_description ?? '');

        return $len >= 120 && $len <= 160;
    }

    protected function has_og_image(SeoPage $page): bool
    {
        return ! empty($page->og_image) || $page->hasMedia('seo-page-og');
    }

    protected function has_focus_keywords(SeoPage $page): bool
    {
        return ! empty($page->focus_keywords) && is_array($page->focus_keywords) && count($page->focus_keywords) > 0;
    }

    protected function is_indexable(SeoPage $page): bool
    {
        return $page->robots === 'index_follow';
    }

    protected function has_canonical(SeoPage $page): bool
    {
        return ! empty($page->canonical_url);
    }

    protected function meta_title_has_keyword(SeoPage $page): bool
    {
        $keywords = $page->focus_keywords;
        if (empty($keywords) || ! is_array($keywords)) {
            return false;
        }
        $firstKeyword = $keywords[0] ?? null;
        if (empty($firstKeyword)) {
            return false;
        }

        return mb_stripos($page->meta_title ?? '', $firstKeyword) !== false;
    }

    protected function meta_description_has_keyword(SeoPage $page): bool
    {
        $keywords = $page->focus_keywords;
        if (empty($keywords) || ! is_array($keywords)) {
            return false;
        }
        $firstKeyword = $keywords[0] ?? null;
        if (empty($firstKeyword)) {
            return false;
        }

        return mb_stripos($page->meta_description ?? '', $firstKeyword) !== false;
    }

    protected function has_schema(SeoPage $page): bool
    {
        return $page->schemas()->where('is_active', true)->exists();
    }
}
