@extends('layouts.main')
@section('content')<section class="wrap"><h1>{{ $title }}</h1><div class="article-grid">
@forelse($items as $item)<article><h2><a href="{{ $type === 'service' ? route('services.show',$item->slug) : route('blog.show',$item->slug) }}">{{ $item->name ?? $item->title }}</a></h2><p>{{ $item->description ?? $item->meta_description }}</p></article>@empty<p>المحتوى قيد الإعداد والمراجعة.</p>@endforelse
</div>@if(method_exists($items,'links')){{ $items->links() }}@endif</section>@endsection
