@php
    $journeyAppointment = \App\Services\SiteView::draft(\App\Models\SitePage::where('page_key', 'appointment')->first());
    $journeyBookingSection = $sections->firstWhere('section_key', 'booking');
    $journeyCanBook = ($pageKey ?? '') === 'journey' && $journeyAppointment?->is_published && $journeyBookingSection;
@endphp
<section class="wrap personal-journey journey-wizard" id="your-journey">
    <div class="journey-intro">
        <div class="journey-character"><x-journey-character /></div>
        <span class="eyebrow"><x-clinic-icon name="compass" :size="20" />{{ $content['field_1'] ?? '' }}</span>
        <{{ $sectionHeadingTag ?? 'h2' }}>{{ $content['field_2'] ?? '' }}<br><em>{{ $content['field_3'] ?? '' }}</em></{{ $sectionHeadingTag ?? 'h2' }}>
        <p>{{ $content['field_4'] ?? '' }}</p>
        <p class="journey-status" aria-live="polite">{{ $content['field_5'] ?? '' }}</p>
    </div>
    <div class="journey-experience">
        @if($journeyCanBook)
            <ol class="journey-wizard-progress" aria-label="خطوات رحلتكِ"><li data-journey-step="1" aria-current="step"><span>1</span>جهّزي رحلتكِ</li><li data-journey-step="2"><span>2</span>اطلبي موعدكِ</li></ol>
        @endif
        <div id="journey-preparation-panel">
            <div class="journey-choices" role="group" aria-label="اختاري مرحلتكِ">
                @foreach(['planning' => ['field_6', 'compass'], 'pregnant' => ['field_8', 'calendar'], 'postpartum' => ['field_9', 'clinic'], 'health' => ['field_10', 'badge']] as $stageKey => [$field, $icon])
                    <button type="button" data-stage="{{ $stageKey }}" aria-pressed="false" @disabled(empty($journey[$stageKey] ?? null))><x-clinic-icon :name="$icon" :size="20" /><span>{{ $content[$field] ?? '' }}</span></button>
                @endforeach
            </div>
            <div id="journey-result" hidden>
                <span class="eyebrow" id="journey-tag"></span><h3 id="journey-title"></h3><p id="journey-description"></p>
                <div class="journey-content">
                    <div class="journey-question-panel">
                        <h4><x-clinic-icon name="clipboard" :size="20" />{{ $content['field_11'] ?? '' }}</h4>
                        <ul id="journey-questions"></ul>
                        <form id="journey-question-form">
                            <label for="journey-personal-question">سؤالكِ الخاص للدكتور</label>
                            <textarea id="journey-personal-question" rows="2" maxlength="240" placeholder="اكتبي السؤال اللي حابة تناقشيه في الزيارة" aria-describedby="journey-question-hint"></textarea>
                            <div class="journey-question-actions"><small id="journey-question-hint">حتى 10 أسئلة لكل مرحلة، وبتظهر في بطاقة رحلتكِ.</small><button type="submit" class="button outline"><x-clinic-icon name="message" :size="17" />أضيفي السؤال</button></div>
                            <p id="journey-question-status" role="status" class="fine"></p>
                        </form>
                    </div>
                    <div class="journey-video-box">
                        <h4><x-clinic-icon name="play" :size="20" />{{ $content['field_12'] ?? '' }}</h4>
                        <button type="button" id="journey-video" data-reel="" hidden>
                            <span class="journey-video-preview"><img id="journey-video-poster" width="320" height="200" alt="" loading="lazy"><span class="journey-video-play"><x-clinic-icon name="play-triangle" :size="22" /></span></span>
                            <span id="journey-video-title"></span>
                        </button>
                        <a id="journey-video-fallback" href="https://www.facebook.com/HelalClinic0" target="_blank" rel="noopener">تصفّحي محتوى الدكتور على فيسبوك <x-clinic-icon name="external" :size="16" /></a>
                        <p id="journey-video-note"></p>
                    </div>
                </div>
                <label for="journey-name">{{ $content['field_14'] ?? '' }}<span>{{ $content['field_15'] ?? '' }}</span></label>
                <input id="journey-name" maxlength="40" placeholder="اسمكِ الأول" autocomplete="given-name">
                <div class="journey-result-actions"><button type="button" id="journey-book" class="button" @if(!$journeyCanBook && ($pageKey ?? '') === 'journey') hidden @endif><x-clinic-icon name="calendar" :size="19" />{{ $content['field_16'] ?? '' }}<x-clinic-icon name="arrow-left" :size="18" /></button><button type="button" id="journey-save" class="button outline"><x-clinic-icon name="clipboard" :size="18" />{{ $content['field_17'] ?? '' }}</button></div>
                <p class="fine">{{ $content['field_18'] ?? '' }}</p>
            </div>
            <div id="journey-placeholder"><h3>{{ $content['field_19'] ?? '' }}</h3><p>{{ $content['field_20'] ?? '' }}</p></div>
        </div>
        @if($journeyCanBook)
            <div id="journey-booking-panel" hidden>
                <button type="button" id="journey-return" class="text-link"><x-clinic-icon name="arrow-left" class="journey-return-icon" :size="18" />ارجعي لرحلتكِ وأسئلتكِ</button>
                @include('helal.sections.booking', ['content' => $journeyBookingSection->content, 'sectionHeadingTag' => 'h2', 'journeyEmbedded' => true])
            </div>
        @endif
    </div>
</section>
