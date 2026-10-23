<section class="wrap faq" id="faq">
    <span class="eyebrow"><x-clinic-icon name="help" :size="19" />{{ $content['field_1'] ?? '' }}</span>
    <{{ $sectionHeadingTag ?? 'h2' }}>{{ $content['field_2'] ?? '' }}</{{ $sectionHeadingTag ?? 'h2' }}>
    @foreach($section->faqs ?? [] as $faq)
        @php
            $question = $faq['question'];
            $questionIcon = str_contains($question, 'مؤك') ? 'check' : ((str_contains($question, 'موعد') || str_contains($question, 'أحجز')) ? 'calendar' : ((str_contains($question, 'جهز') || str_contains($question, 'جهّز')) ? 'clipboard' : 'message'));
        @endphp
        <details>
            <summary><span class="faq-question-icon"><x-clinic-icon :name="$questionIcon" :size="21" /></span><span class="faq-question-text">{{ $question }}</span><span class="faq-toggle"><x-clinic-icon name="chevron-down" :size="18" /></span></summary>
            <p>{{ $faq['answer'] }}</p>
        </details>
    @endforeach
</section>
