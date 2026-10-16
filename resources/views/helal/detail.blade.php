@extends('layouts.main')
@section('content')<article class="wrap"><h1>{{ $item->name ?? $item->title }}</h1><div class="prose max-w-none">{!! str($item->content ?? $item->rich_description ?? $item->description ?? '')->sanitizeHtml() !!}</div><a class="button" href="{{ route('home') }}#booking">طلب موعد</a></article>@endsection
