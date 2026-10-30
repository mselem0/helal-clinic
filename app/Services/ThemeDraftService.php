<?php

namespace App\Services;

use App\Helpers\SeoHelper;
use App\Helpers\SiteSettingHelper;
use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\PageSection;
use App\Models\SeoPage;
use App\Models\Service;
use App\Models\SiteNavigation;
use App\Models\SitePage;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\TvInterview;
use App\Models\ThemeRevision;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ThemeDraftService
{
    public const THEMES = ['helal_classic' => 'هلال الكلاسيكي', 'helal_modern' => 'هلال العصري'];

    public const PUBLISHED_MODELS = [SitePage::class, SiteNavigation::class, SeoPage::class];

    public static function validatePageDraft(SitePage $page, array $data): void
    {
        $copy = clone $page;
        $copy->forceFill($data);
        $reserved = SitePage::RESERVED_SLUGS;
        $invalid = ! preg_match('/^[\pL\pN]+(?:[-_][\pL\pN]+)*$/u', $copy->slug ?? '') || in_array(strtolower($copy->slug), $reserved, true);
        $invalid = $invalid || SitePage::where('slug', $copy->slug)->where('id', '!=', $page->id ?? 0)->exists();
        foreach (SitePage::whereNotNull('draft_data')->where('id', '!=', $page->id ?? 0)->get() as $other) {
            $invalid = $invalid || ($other->draft_data['slug'] ?? $other->slug) === $copy->slug;
        }
        if ($invalid) {
            throw ValidationException::withMessages(['slug' => 'الرابط محجوز أو مستخدم، أو يحتوي على رموز غير مسموحة.']);
        }
        if ($page->exists && in_array($page->page_key, ['home', 'services', 'articles', 'branches'], true) && $copy->slug !== $page->slug) {
            throw ValidationException::withMessages(['slug' => 'رابط هذه الصفحة ثابت.']);
        }
        $keys = collect($copy->sections ?? [])->map(fn ($v) => is_array($v) ? ($v['key'] ?? '') : $v);
        if ($keys->duplicates()->isNotEmpty() || PageSection::whereIn('section_key', $keys)->whereNotIn('section_key', $page->page_key === 'home' ? ['header', 'footer'] : ['header', 'footer', 'home'])->count() !== $keys->count()) {
            throw ValidationException::withMessages(['sections' => 'اختاري أقسامًا متاحة دون تكرار.']);
        }
    }

    public function saveSectionDraft(string $sectionKey, array $data): void
    {
        $section = PageSection::where('section_key', $sectionKey)->firstOrFail();
        $section->update(['draft_data' => array_replace($section->draft_data ?? [], $data)]);
    }

    public function snapshot(): array
    {
        return [
            'documents' => collect(self::PUBLISHED_MODELS)->mapWithKeys(fn ($class) => [$class => $class::all()->map(fn ($model) => collect($model->attributesToArray())->except('draft_data')->all())->all()])->all(),
            'sections' => PageSection::all()->map->only(['id', 'section_key', 'section_label', 'title', 'subtitle', 'body', 'button_text', 'button_url', 'background_color', 'order', 'is_active'])->all(),
            'settings' => SiteSetting::where('group', 'appearance')->get()->map->only(['key', 'value', 'group'])->all(),
            'content' => collect([Service::class, ClinicBranch::class, Video::class, TvInterview::class, Testimonial::class, Article::class])->mapWithKeys(fn ($class) => [$class => $class::all()->map(fn ($model) => $model->attributesToArray())->all()])->all(),
        ];
    }

    public function publish(): bool
    {
        return DB::transaction(function (): bool {
            // Serialize publication and revision restore using the shared active-theme row.
            SiteSetting::where('key', 'active_theme')->lockForUpdate()->firstOrFail();
            ThemeRevision::create(['user_id' => auth()->id(), 'content_snapshot' => $this->snapshot(), 'summary' => 'نسخة قبل النشر']);
            foreach (PageSection::whereNotNull('draft_data')->get() as $section) {
                $section->update(array_merge($section->draft_data, ['draft_data' => null, 'published_at' => now()]));
            }
            foreach (SiteSetting::where('group', 'appearance')->whereNotNull('draft_data')->get() as $setting) {
                $setting->update(['value' => $setting->draft_data['value'], 'draft_data' => null]);
            }
            foreach (self::PUBLISHED_MODELS as $class) {
                foreach ($class::whereNotNull('draft_data')->get() as $record) {
                    $data = $record->draft_data;
                    if ($record instanceof SitePage) {
                        self::validatePageDraft($record, $data);
                        if (($data['is_published'] ?? $record->is_published) && ! $record->published_at) {
                            $data['published_at'] = now();
                        }
                        if (SeoPage::where('page_key', $record->page_key)->exists()) {
                            SeoPage::where('page_key', $record->page_key)->first()->update(collect($data)->only(['meta_title', 'meta_description', 'og_image', 'canonical_url', 'robots'])->all());
                        }
                    }
                    $record->fill($data);
                    $record->draft_data = null;
                    $record->save();
                }
            }
            SeoHelper::clearCache();
            SiteSettingHelper::clearCache();

            return true;
        });
    }

    public function discardDrafts(): bool
    {
        return DB::transaction(function (): bool {
            foreach (self::PUBLISHED_MODELS as $class) {
                $class::query()->update(['draft_data' => null]);
            }
            PageSection::query()->update(['draft_data' => null]);
            SiteSetting::where('group', 'appearance')->update(['draft_data' => null]);

            return true;
        });
    }

    public function restoreRevision(int $id): void
    {
        DB::transaction(function () use ($id): void {
            SiteSetting::where('key', 'active_theme')->lockForUpdate()->firstOrFail();
            $revision = ThemeRevision::findOrFail($id);
            ThemeRevision::create(['user_id' => auth()->id(), 'content_snapshot' => $this->snapshot(), 'summary' => 'نسخة قبل الاستعادة']);
            foreach ($revision->content_snapshot['sections'] as $data) {
                $key = $data['section_key'];
                unset($data['id']);
                PageSection::updateOrCreate(['section_key' => $key], $data + ['draft_data' => null]);
            }
            foreach ($revision->content_snapshot['settings'] as $data) {
                SiteSetting::updateOrCreate(['key' => $data['key']], $data + ['draft_data' => null]);
            }
            foreach ($revision->content_snapshot['documents'] ?? [] as $class => $rows) {
                abort_unless(in_array($class, self::PUBLISHED_MODELS, true), 422);
                if ($class === SitePage::class) {
                    $class::whereNotIn('id', array_column($rows, 'id'))->update(['is_published' => false]);
                }
                if ($class === SeoPage::class) {
                    $class::whereNotIn('id', array_column($rows, 'id'))->delete();
                }
                foreach ($rows as $row) {
                    $record = $class::find($row['id']) ?? new $class;
                    $record->forceFill($row);
                    $record->draft_data = null;
                    $record->save();
                }
            }
            foreach ($revision->content_snapshot['content'] ?? [] as $class => $rows) {
                abort_unless(in_array($class, [Service::class, ClinicBranch::class, Video::class, TvInterview::class, Testimonial::class, Article::class], true), 422);
                $ids = array_column($rows, 'id');
                $flag = $class === Article::class ? 'is_published' : 'is_active';
                $class::whereNotIn('id', $ids)->update([$flag => false]);
                foreach ($rows as $row) {
                    $record = $class::find($row['id']) ?? new $class;
                    $record->forceFill($row)->save();
                }
            }
            $this->discardDrafts();
            SeoHelper::clearCache();
            SiteSettingHelper::clearCache();
        });
    }

    public function applyDrafts($models, ?string $sectionKey = null)
    {
        if (! request()->has('preview') || ! request()->hasValidSignature()) {
            return $models;
        }
        $apply = function (Model $model): Model {
            $copy = clone $model;
            foreach ($model->draft_data ?? [] as $key => $value) {
                $copy->setAttribute($key, $value);
            }

            return $copy;
        };
        if ($models instanceof Model) {
            return $apply($models);
        }

        return $models?->map($apply);
    }
}
