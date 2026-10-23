<section class="wrap videos" id="videos">
    <div class="section-top">
        <div><span class="eyebrow">{{ $content['field_1'] ?? '' }}</span><{{ $sectionHeadingTag ?? 'h2' }}>{{ $content['field_2'] ?? '' }}</{{ $sectionHeadingTag ?? 'h2' }}></div>
        {{-- Temporarily hidden; retain the editable section introduction.
        <p>{{ $content['field_3'] ?? '' }}<br>{{ $content['field_4'] ?? '' }}</p>
        --}}
    </div>
    <div class="reels-grid">
        @foreach($videos as $item)
            @php($poster = \App\Support\ContentUrl::resolve($item->getFirstMediaUrl('video-thumbnail') ?: $item->image_path))
            @php($source = \App\Support\VideoSource::resolve($item->external_video_url ?: ($item->getFirstMediaUrl('video-file') ?: $item->video_path), $item->reel))
            <article class="video-tile">
                <div class="tile-art">
                    <img width="640" height="800" class="real-thumbnail" src="{{ $poster }}" alt="{{ $item->title }}" loading="lazy">
                    @if($item->category)<span class="video-label">{{ $item->category }}</span>@endif
                    <button data-reel="{{ $item->reel ?: $item->id }}" data-title="{{ $item->title }}" data-src="{{ $source }}" data-poster="{{ $poster }}" aria-label="تشغيل {{ $item->title }}" type="button"><x-clinic-icon name="play-triangle" :size="20" /></button>
                </div>
                <div class="tile-copy">
                    <h3>{{ $item->title }}</h3>
                    <p>{{ $item->description }}</p>
                    <button type="button" class="video-watch" data-reel="{{ $item->reel ?: $item->id }}" data-title="{{ $item->title }}" data-src="{{ $source }}" data-poster="{{ $poster }}"><x-clinic-icon name="play-triangle" :size="17" /><span>شاهدي الفيديو</span></button>
                </div>
            </article>
        @endforeach
    </div>
    <div class="video-section-footer">
        @if(filled($content['field_49'] ?? ''))<p class="fine"><x-clinic-icon name="info" :size="17" /><span>{{ $content['field_49'] }}</span></p>@endif
        @if(request()->routeIs('home'))
            <a class="button outline" href="{{ route('gallery') }}"><x-clinic-icon name="play" :size="20" /><span>كل فيديوهات الدكتور</span><x-clinic-icon name="arrow-left" :size="18" /></a>
        @elseif(!empty($content['field_50']))
            <a class="button outline" href="{{ \App\Support\ContentUrl::resolve($content['field_50']) }}" target="_blank" rel="noopener">{{ $content['field_51'] ?? 'المزيد من الفيديوهات' }}</a>
        @endif
    </div>
</section>
