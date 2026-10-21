@php($footerContent = $sections->firstWhere('section_key', 'footer')?->content ?? [])
<section class="contact-channels wrap" aria-labelledby="contact-channels-title">
    <h2 id="contact-channels-title">تواصلي مع فريق العيادة</h2>
    <p>للاستفسارات أو لتأكيد موعد زيارتكِ، تواصلي مع فريق العيادة عبر القنوات التالية.</p>
    <div class="contact-actions">
        @if(!empty($footerContent['field_25']) && !empty($footerContent['field_26']))
            <a class="button outline" href="{{ \App\Support\ContentUrl::resolve($footerContent['field_25']) }}" dir="ltr">{{ $footerContent['field_26'] }}</a>
        @endif
        @if(!empty($footerContent['field_27']) && !empty($footerContent['field_28']))
            <a class="button outline" href="{{ \App\Support\ContentUrl::resolve($footerContent['field_27']) }}" dir="ltr">{{ $footerContent['field_28'] }}</a>
        @endif
        @if(!empty($whatsappNumber))
            <a class="button" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsappNumber) }}" target="_blank" rel="noopener">واتساب</a>
        @endif
        @if(!empty($contact['email']))
            <a class="button outline" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
        @endif
    </div>
</section>
