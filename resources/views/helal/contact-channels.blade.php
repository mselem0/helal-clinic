<section class="contact-channels wrap" aria-labelledby="contact-channels-title">
    <{{ $sectionHeadingTag ?? 'h2' }} id="contact-channels-title">تواصلي مع فريق العيادة</{{ $sectionHeadingTag ?? 'h2' }}>
    <p>للحجز أو لتأكيد موعد زيارتكِ، اختاري الفرع المناسب واتصلي بفريقه.</p>
    <div class="contact-actions">
        @foreach($branches as $branch)
            @if(filled($branch->phone))
                <a class="button outline contact-branch-phone" href="tel:{{ preg_replace('/[^+0-9]/', '', $branch->phone) }}"><span>حجز {{ $branch->name }}</span><bdi dir="ltr">{{ $branch->phone }}</bdi></a>
            @endif
        @endforeach
        @if(filled($contact['doctor_phone'] ?? ''))
            <a class="button outline contact-doctor-phone" href="tel:{{ preg_replace('/[^+0-9]/', '', $contact['doctor_phone']) }}"><span>للتواصل مع الطبيب</span><bdi dir="ltr">{{ $contact['doctor_phone'] }}</bdi></a>
        @endif
        @if(!empty($whatsappNumber))
            <a class="button" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsappNumber) }}" target="_blank" rel="noopener">واتساب</a>
        @endif
        @if(!empty($contact['email']))
            <a class="button outline" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
        @endif
    </div>
</section>
