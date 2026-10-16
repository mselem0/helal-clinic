<?php

namespace App\Filament\Resources\SitePageResource\Pages;

use App\Filament\Resources\SitePageResource;
use App\Models\SitePage;
use App\Services\ThemeDraftService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateSitePage extends CreateRecord
{
    protected static string $resource = SitePageResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $page = new SitePage($data + ['page_key' => 'custom_'.Str::uuid()]);
        ThemeDraftService::validatePageDraft($page, $data);
        $page->is_published = false;
        $page->draft_data = $data;
        $page->save();

        return $page;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء مسودة الصفحة';
    }
}
