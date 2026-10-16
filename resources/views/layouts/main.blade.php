<!doctype html><html lang="ar" dir="rtl"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
@include('helal.seo')<link rel="stylesheet" href="{{ asset('helal.css') }}">
@schema(request()->path()) @seoGlobal
@vite(['resources/css/app.css'])
</head><body><header class="site-header"><div class="wrap"><a class="brand" href="{{ route('home') }}"><img width="64" src="{{ asset('assets/logo.png') }}" alt="عيادات هلال">د. محمد هلال</a><nav><a href="{{ route('home') }}">الرئيسية</a> · <a href="{{ route('services') }}">الخدمات</a> · <a href="{{ route('blog.index') }}">المقالات</a> · <a href="{{ route('home') }}#booking">طلب موعد</a></nav></div></header><main class="wrap">@yield('content')</main><footer class="site-footer"><div class="wrap">عيادات د. محمد هلال · <a href="{{ route('home') }}#clinics">الفروع والتواصل</a></div></footer></body></html>
