@php($visitAppointment = \App\Services\SiteView::draft(\App\Models\SitePage::where('page_key', 'appointment')->first()))
<section class="journey visit-steps-section">
    <div class="wrap">
        <div class="visit-steps-heading"><span class="eyebrow">{{ $content['field_1'] ?? '' }}</span><h2>{{ $content['field_2'] ?? '' }}</h2></div>
        <ol class="steps visit-steps-list" aria-label="خطوات طلب الزيارة">
            @foreach([['icon'=>'clinic','number'=>3,'title'=>4,'description'=>5], ['icon'=>'calendar','number'=>6,'title'=>7,'description'=>8], ['icon'=>'check','number'=>9,'title'=>10,'description'=>11]] as $step)
                <li class="visit-step">
                    <div class="step-symbol"><x-clinic-icon :name="$step['icon']" :size="27" /><b>{{ $content['field_'.$step['number']] ?? '' }}</b></div>
                    <div class="visit-step-copy"><h3>{{ $content['field_'.$step['title']] ?? '' }}</h3><p>{{ $content['field_'.$step['description']] ?? '' }}</p></div>
                </li>
            @endforeach
        </ol>
        @if($visitAppointment?->is_published)
            <div class="visit-steps-next"><a class="button" href="{{ \App\Services\SiteView::pageUrl($visitAppointment) }}"><x-clinic-icon name="calendar" :size="19" />اطلبي موعدًا<x-clinic-icon name="arrow-left" :size="18" /></a><p><x-clinic-icon name="info" :size="17" />الموعد يتأكد بعد تواصل فريق العيادة معكِ.</p></div>
        @endif
    </div>
</section>
