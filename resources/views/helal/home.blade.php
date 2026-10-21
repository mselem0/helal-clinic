@extends('layouts.main')
@section('content')
@php($defaultTeasers = [
    'your-journey' => 'اختاري مرحلتكِ وجهّزي أسئلتكِ قبل الزيارة.',
    'booking' => 'اختاري الفرع والخدمة واليوم المفضل، وسيتواصل معكِ فريق العيادة لتأكيد الموعد.',
    'faq' => 'إجابات عن طلب الموعد والاستعداد للزيارة وتأكيد الحجز.',
])
@foreach($pageSections as $section)
    @if(in_array($section->section_key, ['your-journey','faq','booking']))
        @php($destination = \App\Models\SitePage::where('page_key', ['your-journey'=>'journey','faq'=>'faq','booking'=>'appointment'][$section->section_key])->first())
        @if($destination?->is_published)<section class="wrap page-teaser" id="{{ $section->section_key }}"><div><span class="eyebrow">عيادات هلال</span><h2>{{ $destination->title }}</h2><p>{{ filled($destination->intro) ? $destination->intro : ($defaultTeasers[$section->section_key] ?? $destination->meta_description) }}</p></div><a class="button" href="{{ \App\Services\SiteView::pageUrl($destination) }}">{{ $section->section_key === 'booking' ? 'اطلبي موعدًا' : 'اعرفي المزيد' }}</a></section>@endif
    @elseif(view()->exists('helal.sections.'.$section->section_key))
        <div class="section-shell" data-section="{{ $section->section_key }}" @if($section->background_color) style="background-color: {{ $section->background_color }}" @endif>@include('helal.sections.'.$section->section_key, ['content' => $section->content])
        @php($targetKey=['about'=>'about','services'=>'services','articles'=>'articles','clinics'=>'branches'][$section->section_key] ?? null)
        @php($target=$targetKey ? \App\Models\SitePage::where('page_key',$targetKey)->where('is_published',true)->first() : null)
        @if($target)<div class="wrap section-more"><a class="text-link" href="{{ \App\Services\SiteView::pageUrl($target) }}">اكتشفي المزيد: {{ $target->title }} ←</a></div>@endif
        </div>
    @endif
@endforeach
@endsection
