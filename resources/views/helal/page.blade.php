@extends('layouts.main')
@section('content')
<section class="page-masthead"><div class="wrap"><nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span aria-hidden="true"> / </span>@isset($type)<a href="{{ url(['service'=>'services','article'=>'blog','branch'=>'branches'][$type]) }}">{{ ['service'=>'الخدمات','article'=>'المقالات','branch'=>'الفروع'][$type] }}</a><span aria-hidden="true"> / </span>@endisset<span aria-current="page">{{ $page->title }}</span></nav><span class="eyebrow">عيادات د. محمد هلال</span><h1>{{ $page->title }}</h1>@if($page->intro)<p>{{ $page->intro }}</p>@endif</div></section>
@if(!empty($page->body))<div class="wrap prose-content">{!! str($page->body)->sanitizeHtml() !!}</div>@endif
@isset($item)
    @include('helal.content-detail')
@else
    @foreach($page->sections ?? [] as $key)
        @php($section = $sections->firstWhere('section_key', is_array($key) ? ($key['key'] ?? '') : $key))
        @if($section && view()->exists('helal.sections.'.$section->section_key))<div class="section-shell" @if($section->background_color) style="background-color:{{ $section->background_color }}" @endif>@include('helal.sections.'.$section->section_key,['content'=>$section->content])</div>@endif
    @endforeach
    @if($pageKey === 'articles' && $articles instanceof \Illuminate\Pagination\LengthAwarePaginator)<nav class="wrap pagination" aria-label="صفحات المقالات">@if($articles->previousPageUrl())<a class="button outline" href="{{ $articles->previousPageUrl() }}">السابق</a>@endif<span>صفحة {{ $articles->currentPage() }} من {{ $articles->lastPage() }}</span>@if($articles->nextPageUrl())<a class="button" href="{{ $articles->nextPageUrl() }}">التالي</a>@endif</nav>@endif
@endif
@if($pageKey !== 'appointment')<section class="wrap page-teaser"><div><h2>الخطوة التالية تبدأ بسؤال</h2><p>اختاري الفرع المناسب وأرسلي طلب موعد لفريق العيادة.</p></div><a class="button" href="{{ \App\Models\SitePage::where('page_key','appointment')->first()?->url }}">طلب موعد</a></section>@endif
@endsection
