@extends('layouts.main')
@section('content')
@if(($type ?? null) === 'service')
    @include('helal.service-detail')
@else
@php
    $headingSectionKey = ['appointment'=>'booking', 'about'=>'about', 'journey'=>'your-journey', 'videos'=>'videos', 'faq'=>'faq'][$pageKey ?? ''] ?? null;
    $headingSection = $headingSectionKey ? $pageSections->firstWhere('section_key', $headingSectionKey) : null;
    $headingField = $headingSectionKey === 'about' ? 'field_4' : 'field_2';
    $useContentHeading = !isset($item) && (($pageKey ?? '') === 'contact' || ($headingSection && filled($headingSection->content[$headingField] ?? null)));
    $contentHeadingUsed = false;
    $journeyBookingIntegrated = $pageKey === 'journey' && $pageSections->contains('section_key', 'your-journey') && $sections->firstWhere('section_key', 'booking') && \App\Services\SiteView::draft(\App\Models\SitePage::where('page_key', 'appointment')->first())?->is_published;
@endphp
<section class="page-masthead {{ $useContentHeading ? 'breadcrumbs-only' : 'compact-page-heading' }}"><div class="wrap"><nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span aria-hidden="true"> / </span>@isset($type)<a href="{{ url(['service'=>'services','article'=>'blog','branch'=>'branches'][$type]) }}">{{ ['service'=>'الخدمات','article'=>'المقالات','branch'=>'الفروع'][$type] }}</a><span aria-hidden="true"> / </span>@endisset<span aria-current="page">{{ $page->title }}</span></nav>@unless($useContentHeading)<h1>{{ $page->title }}</h1>@endunless@if($page->intro)<p>{{ $page->intro }}</p>@endif</div></section>
@if(!empty($page->body))<div class="wrap prose-content">{!! str($page->body)->sanitizeHtml() !!}</div>@endif
@isset($item)
    @include('helal.content-detail')
@else
    @if($pageKey === 'contact')
        @include('helal.contact-channels', ['sectionHeadingTag' => $useContentHeading ? 'h1' : 'h2'])
    @endif
    @foreach($page->sections ?? [] as $key)
        @continue($pageKey === 'journey' && (is_array($key) ? ($key['key'] ?? '') : $key) === 'booking' && !empty($journeyBookingIntegrated))
        @php($section = $sections->firstWhere('section_key', is_array($key) ? ($key['key'] ?? '') : $key))
        @if($section && view()->exists('helal.sections.'.$section->section_key))<div class="section-shell" data-section="{{ $section->section_key }}" @if($section->background_color) style="background-color:{{ $section->background_color }}" @endif>@include('helal.sections.'.$section->section_key,['content'=>$section->content, 'sectionHeadingTag'=>($useContentHeading && !$contentHeadingUsed && $section->section_key === $headingSectionKey) ? 'h1' : 'h2'])@php($contentHeadingUsed = $contentHeadingUsed || ($useContentHeading && $section->section_key === $headingSectionKey))</div>@endif
    @endforeach
    @if($pageKey === 'about' && !empty($page->doctor_profile))
        @include('helal.doctor-profile', ['profile' => $page->doctor_profile])
    @endif
    @if($pageKey === 'articles' && $articles instanceof \Illuminate\Pagination\LengthAwarePaginator && $articles->hasPages())<nav class="wrap pagination" aria-label="صفحات المقالات">@if($articles->previousPageUrl())<a class="button outline" href="{{ $articles->previousPageUrl() }}">السابق</a>@endif<span>صفحة {{ $articles->currentPage() }} من {{ $articles->lastPage() }}</span>@if($articles->nextPageUrl())<a class="button" href="{{ $articles->nextPageUrl() }}">التالي</a>@endif</nav>@endif
@endif
@if(!in_array($pageKey, ['appointment', 'journey']) && !isset($item))<section class="wrap page-teaser"><div><h2>الخطوة التالية تبدأ بسؤال</h2><p>اختاري الفرع المناسب وأرسلي طلب موعد لفريق العيادة.</p></div><a class="button" href="{{ \App\Models\SitePage::where('page_key','appointment')->first()?->url }}">طلب موعد</a></section>@endif
@endif
@endsection
