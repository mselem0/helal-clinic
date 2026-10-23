@php
    $ratingText = trim($content['field_9'] ?? '');
    $ratingAscii = strtr($ratingText, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9','٫'=>'.']);
    $displayRating = is_numeric($ratingAscii) ? (string) (float) $ratingAscii : $ratingText;
    $reviewNote = trim(str_replace(['عرض ثابت؛ لا يتحدّث تلقائيًا.', 'عرض ثابت؛ لا يتحدث تلقائيًا.'], '', $content['field_37'] ?? ''));
@endphp
<section class="wrap google-reviews" id="reviews">
    <div class="section-top">
        <div><span class="eyebrow">{{ $content['field_1'] ?? '' }}</span><h2>{{ $content['field_2'] ?? '' }}</h2></div>
        <div class="review-overview">
            <span class="google-word" aria-label="Google">@foreach(range(3,8) as $field)<b>{{ $content['field_'.$field] ?? '' }}</b>@endforeach</span>
            <div class="review-summary">
                <strong aria-label="التقييم: {{ $displayRating }} من 5">{{ $displayRating }}</strong>
                <div class="review-summary-detail"><span class="review-stars" aria-hidden="true">{{ $content['field_10'] ?? '' }}</span><span>{{ $content['field_11'] ?? '' }}</span></div>
            </div>
            {{-- @if(filled($content['field_12'] ?? ''))<small class="review-updated">{{ $content['field_12'] }}</small>@endif --}}
        </div>
    </div>
    <div class="review-grid">
        @foreach($testimonials as $item)
            @php($rating = max(1, min(5, $item->rating)))
            <article class="google-review-card">
                <div class="review-person"><span class="review-avatar" aria-hidden="true">{{ $item->patient_initial }}</span><div><strong dir="auto">{{ $item->patient_name }}</strong><small>مقتطف من مراجعة منشورة</small></div><span class="google-g" aria-hidden="true">G</span></div>
                <div class="review-rating-row"><span class="review-stars" aria-label="{{ $rating }} من 5">{{ str_repeat('★', $rating) }}</span></div>
                <blockquote>{{ $item->review_text }}</blockquote>
                @if($item->source_url)<a class="review-source" href="{{ \App\Support\ContentUrl::resolve($item->source_url) }}" target="_blank" rel="noopener"><span>مشاهدة مصدر المراجعة</span><x-clinic-icon name="external-link" :size="16" /></a>@endif
            </article>
        @endforeach
    </div>
    <div class="review-bottom">
        @if(filled($content['field_38'] ?? ''))<a class="button outline" href="{{ \App\Support\ContentUrl::resolve($content['field_38']) }}" target="_blank" rel="noopener"><span>{{ $content['field_39'] ?? 'كل التقييمات على جوجل' }}</span><x-clinic-icon name="external-link" :size="18" /></a>@endif
        @if(filled($reviewNote))<p class="fine"><x-clinic-icon name="info" :size="17" /><span>{{ $reviewNote }}</span></p>@endif
    </div>
</section>
