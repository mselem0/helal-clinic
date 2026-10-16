<!doctype html>
<html lang="ar" dir="rtl" data-theme="{{ $theme }}"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
@include('helal.seo')
<link rel="preload" href="{{ asset('fonts/ibm-plex-sans-arabic-arabic-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin><link rel="stylesheet" href="{{ asset('fonts/ibm-plex-arabic.css') }}">
<link rel="icon" href="{{ asset('assets/logo.png') }}">
<link rel="stylesheet" href="{{ asset('helal.css') }}"><link rel="stylesheet" href="{{ asset('helal-modern.css') }}"><link rel="stylesheet" href="{{ asset('helal-pages.css') }}">
<link rel="stylesheet" href="{{ asset('helal-refinement.css') }}?v={{ substr(hash_file('sha256', public_path('helal-refinement.css')), 0, 12) }}">
<style>:root { --teal: {{ $style['primary'] }}; --orange: {{ $style['accent'] }}; }</style>
@unless($preview) @seoGlobal @endunless
</head><body>
<a class="skip-link" href="#main-content">انتقلي إلى المحتوى</a>
@if($preview)<div class="preview-banner">معاينة خاصة للمسودة · لا تظهر للزوار</div>@endif
@include('helal.sections.header', ['content' => $sections->firstWhere('section_key','header')?->content ?? []])
<main id="main-content" class="{{ request()->routeIs('home') ? 'home-page' : 'inner-page' }}">@yield('content')</main>
@include('helal.sections.footer', ['content' => $sections->firstWhere('section_key','footer')?->content ?? []])
<script>window.helalJourney = @json($journey); window.helalBooking = @json(['url' => route('bookings.store'), 'enabled' => $bookingEnabled]);</script>
<script src="{{ asset('helal.js') }}" defer></script></body></html>
