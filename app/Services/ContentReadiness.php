<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\PageSection;
use App\Models\SitePage;

class ContentReadiness
{
    public function warnings(): array
    {
        $warnings = [];
        foreach (SitePage::all() as $page) {
            if (! $page->meta_description || ! $page->meta_title) {
                $warnings[] = 'بيانات البحث ناقصة: '.$page->title;
            }
        }
        foreach (ClinicBranch::where('is_active', true)->get() as $branch) {
            if (! $branch->phone || ! $branch->working_hours || ! $branch->google_maps_url || ! $branch->service_ids) {
                $warnings[] = 'راجعي بيانات التواصل والمواعيد والخدمات: '.$branch->name;
            }
        }
        foreach (Article::where('is_published', true)->get() as $article) {
            if (! $article->author_name || ! $article->medical_reviewer || ! $article->reviewed_at || ! $article->sources) {
                $warnings[] = 'بيانات التأليف والمراجعة والمصادر ناقصة: '.$article->title;
            }
        }
        $paths = SitePage::where('is_published', true)->get()->map(fn ($p) => parse_url(SiteView::pageUrl($p), PHP_URL_PATH))->all();
        foreach (PageSection::all() as $section) {
            foreach (json_decode($section->body ?? '{}', true)['fields'] ?? [] as $field) {
                $url = $field['value'] ?? '';
                if (($field['type'] ?? '') === 'url' && str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! in_array(parse_url($url, PHP_URL_PATH), $paths, true) && ! preg_match('~^/(services|blog|branches)/~', $url)) {
                    $warnings[] = 'راجعي رابطًا داخليًا في '.$section->section_label.': '.$url;
                }
            }
        }

        return array_values(array_unique($warnings));
    }
}
