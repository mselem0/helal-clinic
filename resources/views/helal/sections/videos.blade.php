<section class="wrap videos" id="videos">
    <div class="section-top">
        <div><span class="eyebrow">{{ $content['field_1'] ?? '' }}</span><h2>{{ $content['field_2'] ?? '' }}</h2></div>
        <p>{{ $content['field_3'] ?? '' }}<br>{{ $content['field_4'] ?? '' }}</p>
    </div>
    <div class="reels-grid">
        @foreach($videos as $item)
            @php($poster = \App\Support\ContentUrl::resolve($item->getFirstMediaUrl('video-thumbnail') ?: $item->image_path))
            @php($source = \App\Support\VideoSource::resolve($item->external_video_url ?: ($item->getFirstMediaUrl('video-file') ?: $item->video_path), $item->reel))
            <article class="video-tile">
                <div class="tile-art">
                    <img width="640" height="800" class="real-thumbnail" src="{{ $poster }}" alt="{{ $item->title }}" loading="lazy">
                    @if($item->category)<span class="video-label">{{ $item->category }}</span>@endif
                    <button data-reel="{{ $item->reel ?: $item->id }}" data-title="{{ $item->title }}" data-src="{{ $source }}" data-poster="{{ $poster }}" aria-label="تشغيل {{ $item->title }}">▶</button>
                </div>
                <div class="tile-copy">
                    <h3>{{ $item->title }}</h3>
                    <p>{{ $item->description }}</p>
                    <button class="video-watch" data-reel="{{ $item->reel ?: $item->id }}" data-title="{{ $item->title }}" data-src="{{ $source }}" data-poster="{{ $poster }}"><span>شاهدي الفيديو</span><span aria-hidden="true">←</span></button>
                </div>
            </article>
        @endforeach
    </div>
    <div class="video-section-footer">
        <p class="fine">{{ $content['field_49'] ?? '' }}</p>
        @if(request()->routeIs('home'))
            <a class="button outline" href="{{ route('gallery') }}">كل فيديوهات الدكتور <span aria-hidden="true">←</span></a>
        @elseif(!empty($content['field_50']))
            <a class="button outline" href="{{ \App\Support\ContentUrl::resolve($content['field_50']) }}" target="_blank" rel="noopener">{{ $content['field_51'] ?? 'المزيد من الفيديوهات' }}</a>
        @endif
    </div>
</section>
