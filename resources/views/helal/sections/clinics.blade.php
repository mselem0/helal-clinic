<section class="wrap clinics" id="clinics">
    <div class="section-top">
        <div><span class="eyebrow">{{ $content['field_1'] ?? '' }}</span><h2>{{ $content['field_2'] ?? '' }}</h2></div>
        <span class="pill"><x-clinic-icon name="pin" :size="17" />{{ $content['field_3'] ?? '' }}</span>
    </div>
    <div class="clinic-grid">
        @foreach($branches as $item)
            <article class="branch-card">
                <div class="branch-card-heading">
                    <span class="branch-card-icon"><x-clinic-icon name="pin" :size="26" /></span>
                    <h3>@if($item->slug)<a href="{{ route('branches.show', $item->slug) }}">{{ $item->name }}</a>@else{{ $item->name }}@endif</h3>
                </div>
                <p class="branch-card-address"><x-clinic-icon name="pin" :size="18" /><span>{{ $item->address }}</span></p>
                @if($item->working_hours)<p class="branch-card-hours"><x-clinic-icon name="clock" :size="17" /><span>{{ $item->working_hours }}</span></p>@endif
                <div class="branch-card-actions">
                    <button type="button" class="button" data-clinic="{{ $item->name }}"><x-clinic-icon name="calendar" :size="18" /><span>طلب موعد</span></button>
                    @if(filled($item->google_maps_url))<a class="branch-map-link" href="{{ \App\Support\ContentUrl::resolve($item->google_maps_url) }}" target="_blank" rel="noopener" aria-label="الخريطة: موقع عيادة {{ $item->name }}"><x-clinic-icon name="google-maps" :size="21" /><span>فتح في خرائط جوجل</span></a>@endif
                </div>
            </article>
        @endforeach
    </div>
    @if(filled($content['field_22'] ?? ''))<p class="fine branch-visit-note"><x-clinic-icon name="check" :size="18" /><span>{{ $content['field_22'] }}</span></p>@endif
</section>
