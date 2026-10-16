<?php

namespace App\Services;

use App\Helpers\SiteSettingHelper;
use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\ThemeRevision;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ThemeDraftService
{
    public const THEMES = ['helal_classic' => 'هلال الكلاسيكي', 'helal_modern' => 'هلال العصري'];

    public function saveSectionDraft(string $sectionKey, array $data): void
    {
        $section = PageSection::where('section_key', $sectionKey)->firstOrFail();
        $section->update(['draft_data' => array_replace($section->draft_data ?? [], $data)]);
    }

    public function snapshot(): array
    {
        return [
            'sections' => PageSection::all()->map->only(['id', 'section_key', 'section_label', 'title', 'subtitle', 'body', 'button_text', 'button_url', 'background_color', 'order', 'is_active'])->all(),
            'settings' => SiteSetting::where('group', 'appearance')->get()->map->only(['key', 'value', 'group'])->all(),
            'content' => collect([Service::class, ClinicBranch::class, Video::class, Testimonial::class, Article::class])->mapWithKeys(fn ($class) => [$class => $class::all()->map(fn ($model) => $model->getAttributes())->all()])->all(),
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
            SiteSettingHelper::clearCache();

            return true;
        });
    }

    public function discardDrafts(): bool
    {
        return DB::transaction(function (): bool {
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
            foreach ($revision->content_snapshot['content'] ?? [] as $class => $rows) {
                abort_unless(in_array($class, [Service::class, ClinicBranch::class, Video::class, Testimonial::class, Article::class], true), 422);
                $ids = array_column($rows, 'id');
                $flag = $class === Article::class ? 'is_published' : 'is_active';
                $class::whereNotIn('id', $ids)->update([$flag => false]);
                foreach ($rows as $row) {
                    $record = $class::find($row['id']) ?? new $class;
                    $record->forceFill($row)->save();
                }
            }
            $this->discardDrafts();
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
