@php
    $featuredVideo = trim($content['field_14'] ?? '');
    $featuredReel = ctype_digit($featuredVideo) ? $featuredVideo : '';
    $featuredItem = $videos->firstWhere('reel', $featuredReel);
    $featuredSource = \App\Support\VideoSource::resolve($featuredItem?->external_video_url ?: ($featuredReel !== '' ? 'assets/reel-'.$featuredReel.'.mp4' : $featuredVideo), $featuredReel);
    $featuredPoster = trim($content['field_15'] ?? '') ?: ($featuredItem?->getFirstMediaUrl('video-thumbnail') ?: $featuredItem?->image_path);
    $featuredCaptionLine = trim($content['field_21'] ?? '');
    $featuredVideoTitle = trim($content['field_20'] ?? '') ?: trim($featuredItem?->title ?? '');
@endphp
<div class="hero-atmosphere" aria-hidden="true">
    <svg class="hero-vector hero-vector-botanical" viewBox="0 0 160 240" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
        <circle cx="73" cy="50" r="20"/><path d="M54 71c-20 18-28 48-24 79 3 25 17 46 39 57M89 71c21 13 35 34 39 58M66 82c-9 17-9 35-4 53M43 136c6 24 25 42 48 42 23 0 39-17 39-37 0-18-13-32-29-32-19 0-29 15-29 31M44 174c2 20 15 37 38 42M110 171l10 43"/><circle cx="95" cy="108" r="13"/><path d="M78 132c7 8 16 13 29 14M62 153c9 7 21 9 32 6"/>
    </svg>
    <svg class="hero-vector hero-vector-crescent" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
        <rect x="15" y="20" width="90" height="65" rx="8"/><path d="M45 85v12M75 85v12M35 100h50M28 32h17"/><path d="M39 45c10-11 25-14 37-8 14 8 16 25 3 34-11 8-28 5-34-6"/><circle cx="63" cy="47" r="7"/><path d="M60 57c-10 2-13 10-6 15 7 4 17 1 18-5M83 35v7M79.5 38.5h7"/>
    </svg>
    <svg class="hero-vector hero-vector-leaf" viewBox="0 0 140 180" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
        <path d="M30 20v31a29 29 0 0 0 58 0V20M30 20h11M77 20h11M59 80v27a28 28 0 0 0 56 0V88"/><circle cx="115" cy="76" r="12"/><circle cx="115" cy="76" r="5"/><path d="M44 113c-18-17-39 4 0 29 39-25 18-46 0-29Z"/>
    </svg>
    <svg class="hero-vector hero-vector-orbit" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
        <rect x="19" y="22" width="82" height="78" rx="10"/><path d="M19 43h82M40 15v14M80 15v14M60 62c-14-13-31 4 0 25 31-21 14-38 0-25Z"/>
    </svg>
</div>
<section class="hero wrap" id="home">
    <div class="hero-copy">
        <span class="eyebrow"><i></i>{{ ($content['field_1'] ?? '') }}</span>
        <p class="doctor-name">{{ ($content['field_2'] ?? '') }}</p>
        <h1>
            {{ ($content['field_4'] ?? '') }}
            {{ ($content['field_5'] ?? '') }} <em>{{ ($content['field_6'] ?? '') }}</em>
        </h1>
        {{-- Temporarily hidden; retain the editable introduction for later.
        <p class="lead">{{ ($content['field_7'] ?? '') }}</p>
        --}}

    </div>

    <div class="hero-details">
        <div class="actions">
            <a class="button" href="{{ \App\Support\ContentUrl::resolve($content['field_24'] ?? '#booking') }}"><x-clinic-icon name="calendar" :size="19" />{{ ($content['field_8'] ?? '') }}</a>
            <a class="text-link" href="{{ \App\Support\ContentUrl::resolve($content['field_25'] ?? '#services') }}">{{ ($content['field_9'] ?? '') }}</a>
        </div>

    </div>

    <div class="hero-visual hero-feature">
        <button
            type="button"
            class="feature-preview"
            data-reel="{{ $featuredReel }}"
            data-src="{{ $featuredSource }}"
            data-poster="{{ \App\Support\ContentUrl::resolve($featuredPoster) }}"
            data-title="{{ $featuredVideoTitle }}"
            @if($featuredVideoTitle !== '' || filled($content['field_19'] ?? '')) aria-labelledby="hero-feature-caption" @endif
            aria-label="{{ $featuredVideoTitle !== '' ? 'شاهدي د. محمد هلال: '.$featuredVideoTitle : 'شاهدي د. محمد هلال' }}"
        >
            <span class="hero-portrait-frame" style="--hero-video-poster: url('{{ \App\Support\ContentUrl::resolve($featuredPoster) }}')">
                <img
                    width="960"
                    height="1280"
                    src="{{ \App\Support\ContentUrl::resolve($featuredPoster) }}" srcset="{{ \App\Support\ResponsiveImage::srcset($featuredPoster) }}" sizes="(max-width: 760px) 90vw, (max-width: 1184px) 72vw, 806px"
                    alt="د. محمد هلال"
                    fetchpriority="high"
                >
                <span class="feature-play" aria-hidden="true"><svg viewBox="0 0 24 24" width="30" height="30" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>

            </span>
            <span class="hero-video-bar">
                <span class="feature-caption" id="hero-feature-caption">
                    <small>{{ ($content['field_19'] ?? '') }}</small>
                    <strong>{{ $featuredVideoTitle }}</strong>
                    @if ($featuredCaptionLine !== '')
                        <span>{{ $featuredCaptionLine }}</span>
                    @endif
                </span>

            </span>
        </button>

        <div class="feature-foot">
            <span><x-clinic-icon name="play" :size="18" />{{ ($content['field_22'] ?? '') }}</span>
            <a href="{{ \App\Support\ContentUrl::resolve($content['field_26'] ?? '#videos') }}">{{ ($content['field_23'] ?? '') }}</a>
        </div>
    </div>

    <div class="hero-foot" aria-label="المؤهلات والعضويات الطبية">
        <div class="hero-credential"><span class="credential-icon"><x-clinic-icon name="academic" :size="28" /></span><div><span>{{ $content['field_10'] ?? '' }}</span><strong>{{ $content['field_11'] ?? '' }}</strong></div></div>
        <div class="hero-credential"><span class="credential-icon"><x-clinic-icon name="badge" :size="28" /></span><div><span>{{ $content['field_12'] ?? '' }}</span><strong>{{ $content['field_13'] ?? '' }}</strong></div></div>
    </div>
</section>
