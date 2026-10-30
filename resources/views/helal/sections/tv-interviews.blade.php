<section class="wrap tv-interviews" id="tv-interviews" aria-labelledby="tv-interviews-title">
    <div class="section-top">
        <div><h2 id="tv-interviews-title">لقاءات تلفزيونية</h2></div>
    </div>
    <div class="tv-interview-grid">
        @foreach($tvInterviews as $interview)
            @php($cover = $interview->cover_path ? \App\Support\ContentUrl::resolve('storage/'.$interview->cover_path) : null)
            <article class="tv-interview-card">
                <button class="tv-interview-cover" type="button" data-tv-interview-id="{{ $interview->youtube_id }}" data-tv-interview-title="{{ $interview->title }}" aria-label="تشغيل {{ $interview->title }}">
                    <img src="{{ $cover ?: 'https://i.ytimg.com/vi/'.$interview->youtube_id.'/hqdefault.jpg' }}" alt="" loading="lazy" referrerpolicy="no-referrer" width="480" height="360">
                    <span class="tv-interview-play" aria-hidden="true"><x-clinic-icon name="play-triangle" :size="24" /></span>
                </button>
                <div class="tv-interview-copy">
                    <h3>{{ $interview->title }}</h3>
                    @if(filled($interview->description))<p>{{ $interview->description }}</p>@endif
                    <button class="tv-interview-watch" type="button" data-tv-interview-id="{{ $interview->youtube_id }}" data-tv-interview-title="{{ $interview->title }}"><x-clinic-icon name="play-triangle" :size="17" /><span>شاهد اللقاء</span></button>
                </div>
            </article>
        @endforeach
    </div>
</section>
