@extends('layouts.main')
@section('content')
<section class="wrap page-teaser booking-confirmation" aria-labelledby="booking-confirmation-title">
<div>
<p class="eyebrow">الخطوة التالية في واتساب</p>
<h1 id="booking-confirmation-title">طلبكِ جاهز للإرسال</h1>
<p>أكملي إرسال الرسالة بالضغط على «إرسال» في تبويب واتساب الذي فتحناه لكِ.</p>
@if($bookingStored)
<p>تم تسجيل طلبكِ للمراجعة. الموعد ما زال قيد التأكيد من فريق العيادة.</p>
@else
<p>الموعد ما زال قيد التأكيد. لم يُسجل طلب موعد على الموقع؛ أرسلي الرسالة في واتساب ليتمكن فريق العيادة من متابعتكِ.</p>
@endif
<p>فتح واتساب وحده لا يرسل الرسالة تلقائيًا.</p>
<div class="booking-confirmation-actions"><a class="button" href="{{ route('home') }}">العودة للرئيسية</a><a class="button outline" href="{{ url('/appointment') }}">طلب موعد آخر</a></div>
</div>
</section>
@endsection
