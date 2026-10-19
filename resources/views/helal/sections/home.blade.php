@php
    $featuredVideo = trim($content['field_14'] ?? '');
    $featuredReel = ctype_digit($featuredVideo) ? $featuredVideo : '';
    $featuredItem = $videos->firstWhere('reel', $featuredReel);
    $featuredSource = \App\Support\VideoSource::resolve($featuredItem?->external_video_url ?: ($featuredReel !== '' ? 'assets/reel-'.$featuredReel.'.mp4' : $featuredVideo), $featuredReel);
    $featuredCaptionLine = trim($content['field_21'] ?? '');
    $featuredVideoTitle = trim($content['field_20'] ?? '') ?: trim($featuredItem?->title ?? '');
@endphp
<section class="hero wrap" id="home">
    @php($heroBackground = \App\Helpers\SeoHelper::getSetting('hero_background_image', 'assets/doctor-brand-banner.webp'))
    @if($heroBackground)<img class="hero-background-art" src="{{ \App\Support\ContentUrl::resolve($heroBackground) }}" alt="" aria-hidden="true" width="1556" height="592">@endif
    <div class="hero-copy">
        <span class="eyebrow"><i></i>{{ ($content['field_1'] ?? '') }}</span>
        <p class="doctor-name">{{ ($content['field_2'] ?? '') }}</p>
        <h1>
            {{ ($content['field_4'] ?? '') }}<br>
            {{ ($content['field_5'] ?? '') }} <em>{{ ($content['field_6'] ?? '') }}</em>
        </h1>
        <p class="lead">{{ ($content['field_7'] ?? '') }}</p>
        <div class="actions">
            <a class="button" href="{{ \App\Support\ContentUrl::resolve($content['field_24'] ?? '#booking') }}">{{ ($content['field_8'] ?? '') }}</a>
            <a class="text-link" href="{{ \App\Support\ContentUrl::resolve($content['field_25'] ?? '#services') }}">{{ ($content['field_9'] ?? '') }}</a>
        </div>
        <div class="hero-foot">
            <span>{{ ($content['field_10'] ?? '') }}<br><b>{{ ($content['field_11'] ?? '') }}</b></span>
            <span>{{ ($content['field_12'] ?? '') }}<br><b>{{ ($content['field_13'] ?? '') }}</b></span>
        </div>
    </div>

    <div class="hero-visual hero-feature">
        <button
            type="button"
            class="feature-preview"
            data-reel="{{ $featuredReel }}"
            data-src="{{ $featuredSource }}"
            data-poster="{{ \App\Support\ContentUrl::resolve($content['field_15'] ?? '') }}"
            data-title="{{ $featuredVideoTitle }}"
            aria-label="{{ $featuredVideoTitle !== '' ? 'شاهدي د. محمد هلال: '.$featuredVideoTitle : 'شاهدي د. محمد هلال' }}"
        >
            <span class="hero-portrait-frame">
                <img
                    width="640"
                    height="640"
                    src="{{ \App\Support\ContentUrl::resolve(($content['field_15'] ?? '')) }}" srcset="{{ \App\Support\ResponsiveImage::srcset(($content['field_15'] ?? '')) }}" sizes="(max-width: 760px) 100vw, 640px"
                    alt="د. محمد هلال"
                    fetchpriority="high"
                >
            </span>
            <span class="hero-video-bar">
                <span class="feature-caption">
                    <small>{{ ($content['field_19'] ?? '') }}</small>
                    <strong>{{ ($content['field_20'] ?? '') }}</strong>
                    @if ($featuredCaptionLine !== '')
                        <span>{{ $featuredCaptionLine }}</span>
                    @endif
                </span>
                <span class="feature-play" aria-hidden="true">{{ ($content['field_18'] ?? '') }}</span>
            </span>
        </button>

        <div class="feature-foot">
            <span><i></i>{{ ($content['field_22'] ?? '') }}</span>
            <a href="{{ \App\Support\ContentUrl::resolve($content['field_26'] ?? '#videos') }}">{{ ($content['field_23'] ?? '') }}</a>
        </div>
    </div>
</section>