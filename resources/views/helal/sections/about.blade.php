@php
    $doctorProfilePage = \App\Services\SiteView::draft(\App\Models\SitePage::where('page_key', 'about')->where('is_published', true)->first());
    $doctorProfile = $doctorProfilePage?->doctor_profile ?? [];
    $aboutSlides = collect();
    if (filled($content['field_5'] ?? '') || filled($content['field_6'] ?? '')) {
        $aboutSlides->push(['icon' => 'doctor', 'label' => 'عن الدكتور', 'title' => $content['field_5'] ?? '', 'detail' => $content['field_6'] ?? '']);
    }
    if (!empty($doctorProfile['qualifications'])) {
        foreach ($doctorProfile['qualifications'] as $qualification) {
            if (filled($qualification['title'] ?? '')) $aboutSlides->push(['icon' => 'academic', 'label' => 'المؤهلات العلمية', 'title' => $qualification['title'], 'detail' => $qualification['detail'] ?? '']);
        }
    } else {
        foreach (['field_7', 'field_8', 'field_9'] as $field) {
            if (filled($content[$field] ?? '')) $aboutSlides->push(['icon' => 'academic', 'label' => 'المؤهلات العلمية', 'title' => $content[$field], 'detail' => '']);
        }
    }
@endphp
<section class="about-band" id="about">
    <div class="wrap about">
        <div class="about-image">
            <img width="640" height="640" src="{{ \App\Support\ContentUrl::resolve($content['field_1'] ?? '') }}" srcset="{{ \App\Support\ResponsiveImage::srcset($content['field_1'] ?? '') }}" sizes="(max-width: 760px) 100vw, 640px" loading="lazy" alt="الدكتور محمد هلال">
            <span>{{ $content['field_2'] ?? '' }}</span>
        </div>
        <div class="about-copy">
            <span class="eyebrow">{{ $content['field_3'] ?? '' }}</span>
            <{{ $sectionHeadingTag ?? 'h2' }}>{{ $content['field_4'] ?? '' }}</{{ $sectionHeadingTag ?? 'h2' }}>
            @if($aboutSlides->isNotEmpty())
                <div class="about-carousel" data-about-carousel role="region" aria-roledescription="عارض شرائح" aria-label="معلومات عن د. محمد هلال">
                    <div class="about-slides">
                        @foreach($aboutSlides as $slide)
                            <div class="about-slide" data-about-slide role="group" aria-roledescription="شريحة" aria-label="{{ $loop->iteration }} من {{ $loop->count }}">
                                <span class="about-slide-label eyebrow"><x-clinic-icon :name="$slide['icon']" :size="20" />{{ $slide['label'] }}</span>
                                <h3>{{ $slide['title'] }}</h3>
                                @if(filled($slide['detail']))<p>{{ $slide['detail'] }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                    <div class="about-carousel-controls" data-about-controls hidden>
                        <div class="about-carousel-arrows">
                            <button type="button" data-about-prev aria-label="المعلومة السابقة"><x-clinic-icon class="about-prev-icon" name="arrow-left" :size="19" /></button>
                            <button type="button" data-about-next aria-label="المعلومة التالية"><x-clinic-icon name="arrow-left" :size="19" /></button>
                        </div>
                        <div class="about-carousel-dots" role="group" aria-label="اختيار المعلومة">
                            @foreach($aboutSlides as $slide)<button type="button" data-about-dot="{{ $loop->index }}" aria-label="عرض المعلومة {{ $loop->iteration }}: {{ $slide['title'] }}"></button>@endforeach
                        </div>
                    </div>
                    <span class="about-carousel-status" data-about-status aria-live="polite"></span>
                </div>
            @endif
            <div class="about-actions">
                <a class="text-link" href="{{ \App\Support\ContentUrl::resolve($content['field_11'] ?? '#booking') }}"><x-clinic-icon name="calendar" :size="19" />{{ $content['field_10'] ?? '' }}</a>
                @if(request()->routeIs('home', 'site.preview') && ($pageKey ?? '') === 'home' && $doctorProfilePage?->is_published)
                    <a class="button outline" href="{{ \App\Services\SiteView::pageUrl($doctorProfilePage) }}">تعرّفي على د. محمد هلال <x-clinic-icon name="arrow-left" :size="18" /></a>
                @endif
            </div>
        </div>
    </div>
</section>
