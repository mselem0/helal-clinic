<?php

namespace App\Support;

trait TracksPublishedUpdate
{
    protected static function bootTracksPublishedUpdate(): void
    {
        static::saving(function ($record): void {
            $internal = ['id', 'created_at', 'updated_at', 'public_updated_at', 'draft_data', 'seo_score', 'score_breakdown', 'readability_score', 'focus_keywords'];
            if (array_diff(array_keys($record->getDirty()), $internal) !== []) {
                $record->public_updated_at = now();
            } elseif ($record->exists && $record->isDirty('public_updated_at')) {
                $record->public_updated_at = $record->getRawOriginal('public_updated_at');
            }
        });
    }
}
