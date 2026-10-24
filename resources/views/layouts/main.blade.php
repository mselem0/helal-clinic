<!doctype html>
<html lang="ar" dir="rtl" data-theme="{{ $theme }}"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
@if(app()->environment('production') && ! $preview)
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-5HD4L3FH');</script>
@endif
@include('helal.seo')
<link rel="preload" href="{{ asset('fonts/ibm-plex-sans-arabic-arabic-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
@php($siteFavicon = \App\Helpers\SeoHelper::getSetting('site_favicon'))
@if($siteFavicon)
<link rel="icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteFavicon) }}">
@else
<link rel="icon" type="image/png" sizes="96x96" href="{{ asset('assets/favicon-96.png') }}">
<link rel="icon" type="image/png" sizes="144x144" href="{{ asset('assets/favicon-144.png') }}">
<link rel="shortcut icon" href="{{ asset('assets/favicon.ico') }}">
@endif
@php($siteCss = 'site-assets/'.($theme === 'helal_modern' ? 'modern' : 'classic').'.css')
<link rel="stylesheet" href="{{ asset($siteCss) }}?v={{ substr(hash_file('sha256', public_path($siteCss)), 0, 12) }}">
<style>:root { --teal: {{ $style['primary'] }}; --orange: {{ $style['accent'] }}; }</style>
@unless($preview) @seoGlobal @endunless
</head><body id="page-top">
<a class="skip-link" href="#main-content">انتقلي إلى المحتوى</a>
@if($preview)<div class="preview-banner">معاينة خاصة للمسودة · لا تظهر للزوار</div>@endif
@include('helal.sections.header', ['content' => $sections->firstWhere('section_key','header')?->content ?? []])
<main id="main-content" class="{{ request()->routeIs('home') ? 'home-page' : 'inner-page' }}">@yield('content')</main>
@include('helal.sections.footer', ['content' => $sections->firstWhere('section_key','footer')?->content ?? []])
@php($journeyWithVideoSources = collect($journey)->map(function ($stage) use ($videoLibrary, $videos) {
    $reel = (string) ($stage['reel'] ?? '');
    $video = ($videoLibrary ?? $videos)->firstWhere('reel', $reel);
    $stage['reel'] = $video ? $reel : null;
    $stage['source'] = $video ? \App\Support\VideoSource::resolve($video->external_video_url ?: ($video->getFirstMediaUrl('video-file') ?: $video->video_path), $video->reel) : '';
    $stage['poster'] = $video ? \App\Support\ContentUrl::resolve($video->getFirstMediaUrl('video-thumbnail') ?: $video->image_path) : '';
    return $stage;
}))
@php($bookingSchedules = $branches->mapWithKeys(fn ($branch) => [(string) $branch->id => ['enabled' => (bool) $branch->booking_schedule_enabled, 'weekly' => $branch->weekly_schedule ?? [], 'exceptions' => $branch->schedule_exceptions ?? [], 'legacy_hours' => $branch->working_hours]]))
<script>window.helalJourney = @json($journeyWithVideoSources); window.helalBooking = @json(['url' => route('bookings.store'), 'enabled' => $bookingEnabled, 'schedules' => $bookingSchedules]);</script>
<script src="{{ asset('journey-card.js') }}?v={{ substr(hash_file('sha256', public_path('journey-card.js')), 0, 12) }}" defer></script>
<script src="{{ asset('helal.js') }}?v={{ substr(hash_file('sha256', public_path('helal.js')), 0, 12) }}" defer></script></body></html>
