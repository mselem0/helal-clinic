@extends('layouts.main')
@section('content')
@php
    $linkedPages = \App\Models\SitePage::whereIn('page_key', ['journey', 'faq', 'appointment', 'about', 'services', 'articles', 'branches'])
        ->get()->map(fn ($linkedPage) => \App\Services\SiteView::draft($linkedPage))->keyBy('page_key');
@endphp
@foreach($pageSections as $section)
    @continue($section->section_key === 'articles' && $articles->isEmpty())
    @continue($section->section_key === 'videos' && $videos->isEmpty())
    @continue($section->section_key === 'tv-interviews' && $tvInterviews->isEmpty())
    @continue($section->section_key === 'reviews' && $testimonials->isEmpty())
    @if($section->section_key === 'your-journey')
        @php($destination = $linkedPages->get('journey'))
        @if($destination?->is_published)
            <section class="home-journey-band" id="your-journey">
                <div class="wrap home-journey-inner">
                    <div class="home-journey-overview">
                        <div class="home-journey-character"><x-journey-character /></div>
                        <span class="eyebrow"><x-clinic-icon name="compass" :size="20" />خطوة أقرب لفهم احتياجكِ</span>
                        <h2>{{ $destination->title }}</h2>
                        <p>{{ filled($destination->intro) ? $destination->intro : 'اختاري مرحلتكِ وجهّزي أسئلتكِ قبل الزيارة.' }}</p>
                        <a class="button" href="{{ \App\Services\SiteView::pageUrl($destination) }}">ابدئي رحلتكِ <x-clinic-icon name="arrow-left" :size="19" /></a>
                    </div>
                    <ol class="home-journey-preview" aria-label="كيف تساعدكِ رحلة المريضة">
                        <li><span class="journey-preview-icon"><x-clinic-icon name="compass" :size="23" /></span><div><strong>اختاري مرحلتكِ</strong><p>تعرّفي على المعلومات الأقرب لاحتياجكِ.</p></div></li>
                        <li><span class="journey-preview-icon"><x-clinic-icon name="message" :size="23" /></span><div><strong>جهّزي أسئلتكِ</strong><p>رتّبي ما تودّين مناقشته مع الطبيب.</p></div></li>
                        <li><span class="journey-preview-icon"><x-clinic-icon name="calendar" :size="23" /></span><div><strong>استعدّي لزيارتكِ</strong><p>خدي خطوتكِ التالية وإنتِ عارفة تبدئي منين.</p></div></li>
                    </ol>
                </div>
            </section>
        @endif
    @elseif($section->section_key === 'faq')
        @php($destination = $linkedPages->get('faq'))
        @if($destination?->is_published && !empty($section->faqs))
            <div class="section-shell home-faq" data-section="faq">
                @include('helal.sections.faq', ['content' => $section->content])
                <div class="wrap section-more"><a class="button outline faq-all-link" href="{{ \App\Services\SiteView::pageUrl($destination) }}"><x-clinic-icon name="help" :size="20" /><span>كل الأسئلة الشائعة</span><x-clinic-icon name="arrow-left" :size="18" /></a></div>
            </div>
        @endif
    @elseif($section->section_key === 'booking')
        @php($destination = $linkedPages->get('appointment'))
        @if($destination?->is_published)
            <section class="home-booking" id="booking">
                <div class="wrap home-booking-inner">
                    <div><span class="eyebrow">{{ $section->content['field_1'] ?? 'خطوتكِ الأولى تبدأ هنا' }}</span><h2>{{ $destination->title }}</h2><p>{{ filled($destination->intro) ? $destination->intro : 'اختاري الفرع والخدمة واليوم المفضل، وسيتواصل معكِ فريق العيادة لتأكيد الموعد.' }}</p></div>
                    <div class="home-booking-actions">
                        <a class="button home-booking-cta" href="{{ \App\Services\SiteView::pageUrl($destination) }}"><span class="booking-cta-icon"><x-clinic-icon name="calendar" :size="25" /></span><span class="booking-cta-label">اطلبي موعدًا</span><span class="booking-cta-arrow"><x-clinic-icon name="arrow-left" :size="20" /></span></a>
                        @php($bookingBranch = $branches->first(fn ($branch) => filled($branch->phone)))
                        @if($bookingBranch)
                            <a class="home-booking-phone" href="tel:{{ preg_replace('/[^+0-9]/', '', $bookingBranch->phone) }}"><x-clinic-icon name="phone" :size="19" />حجز {{ $bookingBranch->name }} <bdi dir="ltr">{{ $bookingBranch->phone }}</bdi></a>
                        @endif
                        <p class="fine">{{ $section->content['field_9'] ?? 'الطلب لا يؤكد الموعد؛ يتواصل معكِ فريق العيادة للتأكيد.' }}</p>
                    </div>
                </div>
            </section>
        @endif
    @elseif(view()->exists('helal.sections.'.$section->section_key))
        <div class="section-shell" data-section="{{ $section->section_key }}" @if($section->background_color) style="background-color: {{ $section->background_color }}" @endif>
            @include('helal.sections.'.$section->section_key, ['content' => $section->content])
            @php($targetKey = ['about'=>'about', 'services'=>'services', 'articles'=>'articles', 'clinics'=>'branches'][$section->section_key] ?? null)
            @php($target = $targetKey ? $linkedPages->get($targetKey) : null)
            @if($target?->is_published && $section->section_key !== 'about')
                <div class="wrap section-more">
                    @if($section->section_key === 'services')
                        <a class="all-services-link" href="{{ \App\Services\SiteView::pageUrl($target) }}"><x-clinic-icon name="services" :size="20" /><span>كل خدمات العيادة</span><x-clinic-icon name="arrow-left" :size="18" /></a>
                    @elseif($section->section_key === 'clinics')
                        <a class="all-services-link all-branches-link" href="{{ \App\Services\SiteView::pageUrl($target) }}"><x-clinic-icon name="map" :size="20" /><span>تفاصيل فروع العيادة</span><x-clinic-icon name="arrow-left" :size="18" /></a>
                    @else
                        <a class="text-link" href="{{ \App\Services\SiteView::pageUrl($target) }}">{{ ['articles'=>'كل المقالات الطبية', 'clinics'=>'تفاصيل فروع العيادة'][$section->section_key] }} <span aria-hidden="true">←</span></a>
                    @endif
                </div>
            @endif
        </div>
    @endif
@endforeach
@endsection
