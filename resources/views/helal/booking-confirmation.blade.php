@extends('layouts.main')
@section('content')
<section class="wrap page-teaser booking-confirmation" aria-labelledby="booking-confirmation-title">
<div class="booking-confirmation-content">
<div class="booking-confirmation-check" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none"><path d="m18 33 9 9 20-21" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
<p class="eyebrow">شكرًا لثقتكِ بعيادات هلال</p>
<h1 id="booking-confirmation-title">طلب موعدكِ جاهز</h1>
<p>جارٍ تأكيد موعدكِ من فريق العيادة.</p>
<p>نتطلع لاستقبالكِ، ونتمنى لكِ دوام الصحة والعافية.</p>
<div class="booking-confirmation-actions"><a class="button" href="{{ route('home') }}">العودة للرئيسية</a><a class="button outline" href="{{ url('/appointment') }}">طلب موعد آخر</a></div>
</div>
</section>
@endsection
