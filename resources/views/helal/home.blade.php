<!doctype html>
<html lang="ar" dir="rtl" data-theme="{{ $theme }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@include('helal.seo')
<link rel="icon" href="{{ asset('assets/logo.png') }}">
<link rel="stylesheet" href="{{ asset('helal.css') }}">
<link rel="stylesheet" href="{{ asset('helal-modern.css') }}">
<style>:root { --teal: {{ $style['primary'] }}; --orange: {{ $style['accent'] }}; }</style>
@schema('home')
@seoGlobal
</head>
<body>
@if($preview)<div class="preview-banner">معاينة خاصة للمسودة · لا تظهر للزوار</div>@endif
@foreach($sections->where('section_key', 'header') as $section)
    @include('helal.sections.header', ['content' => $section->content])
@endforeach
<main id="main-content">
@foreach($sections->whereNotIn('section_key', ['header', 'footer']) as $section)
    @if(view()->exists('helal.sections.'.$section->section_key))
        <div class="section-shell" @if($section->background_color) style="background-color: {{ $section->background_color }}" @endif>
        @include('helal.sections.'.$section->section_key, ['content' => $section->content])
        </div>
    @endif
@endforeach
</main>
@foreach($sections->where('section_key', 'footer') as $section)
    @include('helal.sections.footer', ['content' => $section->content])
@endforeach
<script>window.helalJourney = @json($journey); window.helalBooking = @json(['url' => route('bookings.store'), 'enabled' => $bookingEnabled]);</script>
<script src="{{ asset('helal.js') }}" defer></script>
</body></html>
