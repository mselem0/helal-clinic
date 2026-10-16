<?php

namespace App\Support;

trait AuthorizesSitePage
{
    public static function canAccess(): bool
    {
        return auth()->user()?->can('page_'.class_basename(static::class)) ?? false;
    }

    public function bootAuthorizesSitePage(): void
    {
        abort_unless(static::canAccess(), 403);
    }
}
