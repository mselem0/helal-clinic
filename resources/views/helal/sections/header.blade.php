@php($headerLinks = \App\Services\SiteView::menu('header'))
@php($currentPage = ['service'=>'services','article'=>'articles','branch'=>'branches'][$pageKey ?? ''] ?? ($pageKey ?? 'home'))
<header class="clinic-header"><div class="header-inner">
    <a class="brand header-brand" href="{{ route('home') }}" title="الصفحة الرئيسية">
        <img class="brand-logo" width="56" height="56" src="{{ asset('assets/logo-small.webp') }}" alt="">
        <span>{{ $content['field_2'] ?? 'د. محمد هلال' }}<small>{{ $content['field_3'] ?? 'عيادات هلال' }}</small></span>
    </a>
    <button class="menu-toggle" type="button" aria-label="فتح قائمة الموقع" aria-expanded="false" aria-controls="main-menu"><svg class="menu-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path class="menu-icon-lines" d="M4 6h16M4 12h16M4 18h16"/><path class="menu-icon-close" d="m6 6 12 12M18 6 6 18"/></svg></button>
    <nav id="main-menu" aria-label="القائمة الرئيسية">
        <div class="header-links">@foreach($headerLinks as $link)@if($link['key'] !== 'appointment')
            <a href="{{ $link['url'] }}" @if($currentPage === $link['key']) aria-current="page" @endif>{{ $link['label'] }}</a>
        @endif@endforeach</div>
        <div class="header-actions">
            @if(\App\Support\ClinicFeatures::enabled('PatientCore'))<a class="header-account" href="{{ auth()->check() && auth()->user()->isAdmin() ? route('filament.admin.pages.dashboard') : (auth()->check() ? route('patient.portal') : route('patient.auth')) }}">{{ auth()->check() && auth()->user()->isAdmin() ? 'لوحة الإدارة' : 'حساب المريضة' }}</a>@endif
            @foreach($headerLinks as $link)@if($link['key'] === 'appointment')<a class="button header-booking" href="{{ $link['url'] }}" @if($currentPage === 'appointment') aria-current="page" @endif><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="4"/><path d="M7 3v4M17 3v4M3 11h18m-11 5h4"/></svg>{{ $link['label'] }}</a>@endif@endforeach
        </div>
    </nav>
</div></header>
