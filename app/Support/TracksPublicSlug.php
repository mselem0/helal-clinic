<?php

namespace App\Support;

use App\Models\ClinicBranch;
use App\Models\SeoRedirect;
use App\Models\SitePage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait TracksPublicSlug
{
    public static function bootTracksPublicSlug(): void
    {
        static::saving(function ($model) {
            if (! $model->slug && $model instanceof ClinicBranch) {
                $model->slug = 'branch-'.strtolower(Str::random(10));
            }
            if (! $model->slug && ! $model instanceof SitePage) {
                return;
            }
            if (! preg_match('/^[\pL\pN]+(?:[-_][\pL\pN]+)*$/u', $model->slug ?? '')) {
                throw ValidationException::withMessages(['slug' => 'استخدمي حروفًا وأرقامًا وشرطات فقط.']);
            }
            if ($model instanceof SitePage) {
                $reserved = ['services', 'blog', 'branches', 'bookings', 'site-preview', 'sitemap', 'robots', 'up', 'codeaiadmindash', 'livewire', 'storage', 'assets', 'patient', 'api', 'login', 'logout', 'register', 'password', 'email', 'admin'];
                if (in_array(strtolower($model->slug), $reserved, true)) {
                    throw ValidationException::withMessages(['slug' => 'هذا الرابط محجوز للنظام.']);
                }
                if ($model->exists && $model->getOriginal('page_key') === 'home' && $model->isDirty('slug')) {
                    throw ValidationException::withMessages(['slug' => 'رابط الرئيسية ثابت.']);
                }
            }
        });
        static::updated(function ($model) {
            if (! $model->wasChanged('slug') || ! $model->getOriginal('slug') || ! ($model->getOriginal('is_published') ?? $model->getOriginal('is_active'))) {
                return;
            }
            $new = $model->publicPath();
            $old = dirname($new).'/'.$model->getOriginal('slug');
            $old = '/'.ltrim($old, '/');
            if ($old === $new) {
                return;
            }
            // Repoint incoming redirects and remove a redirect on the now-live destination.
            SeoRedirect::where('source_url', $new)->delete();
            SeoRedirect::where('target_url', $old)->update(['target_url' => $new]);
            SeoRedirect::updateOrCreate(['source_url' => $old], ['target_url' => $new, 'redirect_type' => 301, 'is_active' => true, 'match_type' => 'exact', 'notes' => 'تغيير رابط منشور']);
        });
    }
}
