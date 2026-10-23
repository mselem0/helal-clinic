@php
    $serviceImage = $item->getFirstMediaUrl('service-image') ?: $item->image_path;
    $appointmentPage = \App\Services\SiteView::draft(\App\Models\SitePage::where('page_key', 'appointment')->first());
    $appointmentUrl = $appointmentPage?->is_published ? \App\Services\SiteView::pageUrl($appointmentPage).'?'.http_build_query(['service' => $item->name]) : null;
    $serviceBranches = $branches->filter(fn ($branch) => in_array($item->id, $branch->service_ids ?? []));
    $serviceArticles = $articles->filter(fn ($article) => in_array($item->id, $article->service_ids ?? []));
    $serviceContent = filled($item->rich_description) ? $item->rich_description : null;
@endphp
<section class="service-intro">
    <div class="wrap">
        <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span aria-hidden="true"> / </span><a href="{{ route('services') }}">الخدمات</a><span aria-hidden="true"> / </span><span aria-current="page">{{ $item->name }}</span></nav>
        <div class="service-intro-grid {{ $serviceImage ? 'has-image' : '' }}">
            <div class="service-intro-copy">
                <span class="eyebrow"><x-clinic-icon name="services" :size="19" />رعاية متخصصة في عيادات هلال</span>
                <h1>{{ $page->title }}</h1>
                @if(filled($item->description))<p class="service-intro-description">{{ $item->description }}</p>@endif
                <div class="service-intro-actions">
                    @if($appointmentUrl)<a class="button service-appointment-link" href="{{ $appointmentUrl }}"><x-clinic-icon name="calendar" :size="20" />اطلبي موعدًا<x-clinic-icon name="arrow-left" :size="18" /></a>@endif
                    <a class="text-link" href="#service-information">تعرّفي على تفاصيل الزيارة <span aria-hidden="true">↓</span></a>
                </div>
                <p class="service-appointment-note"><x-clinic-icon name="check" :size="17" />الموعد يتأكد بعد تواصل فريق العيادة معكِ.</p>
            </div>
            @if($serviceImage)
                <div class="service-intro-visual"><img src="{{ \App\Support\ContentUrl::resolve($serviceImage) }}" srcset="{{ \App\Support\ResponsiveImage::srcset($serviceImage) }}" sizes="(max-width: 760px) 90vw, 440px" alt="{{ $item->name }}" width="640" height="480" fetchpriority="high"></div>
            @endif
        </div>
    </div>
</section>
<div class="wrap service-detail-layout" id="service-information">
    <article class="service-detail-content">
        @if(!empty($page->body))<div class="prose-content">{!! str($page->body)->sanitizeHtml() !!}</div>@endif
        @if($serviceContent)
            <h2>عن الخدمة</h2>
            <div class="prose-content">{!! str($serviceContent)->sanitizeHtml() !!}</div>
        @endif
        <section class="service-visit-guide" aria-labelledby="service-visit-heading">
            <span class="eyebrow">زيارة أوضح وأسئلة مرتّبة</span>
            <h2 id="service-visit-heading">قبل زيارتكِ</h2>
            <p>حضّري المعلومات اللي تساعد الطبيب يفهم احتياجكِ، وناقشي معاه الخطوة المناسبة لحالتكِ.</p>
            <ul>
                <li><x-clinic-icon name="message" :size="21" /><div><strong>اكتبي أسئلتكِ</strong><span>رتّبي الأعراض والأسئلة اللي حابة تناقشيها خلال الزيارة.</span></div></li>
                <li><x-clinic-icon name="services" :size="21" /><div><strong>جهّزي أوراقكِ الطبية</strong><span>لو عندكِ تحاليل أو تقارير سابقة وقائمة بالأدوية، خديها معاكِ.</span></div></li>
                <li><x-clinic-icon name="calendar" :size="21" /><div><strong>أكّدي تفاصيل الموعد</strong><span>راجعي الفرع والميعاد مع فريق العيادة قبل الزيارة.</span></div></li>
            </ul>
        </section>
    </article>
    <aside class="related-panel service-side-panel" aria-labelledby="service-branches-heading">
        <span class="eyebrow"><x-clinic-icon name="pin" :size="18" />اختاري المكان المناسب</span>
        <h2 id="service-branches-heading">الفروع التي تقدم الخدمة</h2>
        @forelse($serviceBranches as $branch)
            <a class="service-branch-link" href="{{ route('branches.show', $branch->slug) }}"><x-clinic-icon name="pin" :size="20" /><span><strong>{{ $branch->name }}</strong>@if($branch->address)<small>{{ $branch->address }}</small>@endif</span><x-clinic-icon name="arrow-left" :size="17" /></a>
        @empty
            <p>تواصلي مع فريق العيادة لتحديد الفرع المناسب لهذه الخدمة.</p>
        @endforelse
        @if($appointmentUrl)<a class="button service-appointment-link" href="{{ $appointmentUrl }}"><x-clinic-icon name="calendar" :size="19" />طلب موعد</a><p class="fine">هنساعدكِ في تحديد الفرع والوقت المناسبين.</p>@endif
        @if($serviceArticles->isNotEmpty())
            <div class="service-related-reading"><h2>قراءة مرتبطة بالخدمة</h2>@foreach($serviceArticles as $article)<a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }} <span aria-hidden="true">←</span></a>@endforeach</div>
        @endif
    </aside>
</div>
<div class="wrap service-back-link"><a class="all-services-link" href="{{ route('services') }}"><x-clinic-icon name="services" :size="20" />كل خدمات العيادة<x-clinic-icon name="arrow-left" :size="18" /></a></div>
