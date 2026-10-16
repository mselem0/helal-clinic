<?php

// app/Http/Middleware/InjectSeoMeta.php

namespace App\Http\Middleware;

use App\Helpers\SeoHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class InjectSeoMeta
{
    public function handle(Request $request, Closure $next): Response
    {
        // For this single-page application, we use 'home' as the default key
        $pageKey = $request->path() === '/' ? 'home' : ($request->route()?->getName() ?? $request->path());
        $seoPage = SeoHelper::getPage($pageKey);

        $seo = [
            'meta_title' => $seoPage?->meta_title ?: SeoHelper::getSetting('site_name'),
            'meta_description' => $seoPage?->meta_description ?: SeoHelper::getSetting('homepage_description'),
            'meta_keywords' => $seoPage?->meta_keywords ?: [],
            'og_title' => $seoPage?->og_title ?: $seoPage?->meta_title,
            'og_description' => $seoPage?->og_description ?: $seoPage?->meta_description,
            'canonical_url' => $seoPage?->canonical_url ?: $request->url(),
            'robots' => $seoPage?->robots ?: 'index_follow',
        ];

        $content = null;
        if ($request->routeIs('services.show')) { $content = \App\Models\Service::where('slug',$request->route('slug'))->where('is_active',true)->first(); }
        if ($request->routeIs('blog.show')) { $content = \App\Models\Article::where('slug',$request->route('slug'))->where('is_published',true)->first(); }
        if ($content) {
            $seo['meta_title'] = $content->meta_title ?: ($content->title ?? $content->name).' | عيادات د. محمد هلال';
            $seo['meta_description'] = $content->meta_description ?: $content->description;
        }
        $tracking = [
            'google_analytics_id' => SeoHelper::getSetting('google_analytics_id'),
            'facebook_pixel_id' => SeoHelper::getSetting('facebook_pixel_id'),
            'gtm_id' => SeoHelper::getSetting('gtm_id'),
            'custom_head_scripts' => SeoHelper::getSetting('custom_head_scripts'),
            'custom_body_scripts' => SeoHelper::getSetting('custom_body_scripts'),
        ];

        View::share('site_seo', $seo);
        View::share('site_tracking', $tracking);
        // Appearance and other settings from old SiteSettingHelper can still be used or migrated

        return $next($request);
    }
}
