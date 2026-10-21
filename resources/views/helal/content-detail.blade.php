<div class="wrap detail-grid detail-{{ $type }}"><article class="detail-body{{ $type === 'service' ? ' service-detail-body' : '' }}">
@if($type !== 'branch')
    @php($image = $item->getFirstMediaUrl($type === 'article' ? 'article-image' : 'service-image') ?: $item->image_path)
    @if($image)<img class="detail-image{{ $type === 'service' ? ' service-detail-image' : '' }}" src="{{ \App\Support\ContentUrl::resolve($image) }}" srcset="{{ \App\Support\ResponsiveImage::srcset($image) }}" sizes="(max-width: 760px) 100vw, 960px" alt="{{ $item->title ?? $item->name }}" width="960" height="640" fetchpriority="high">@endif
    @if($type === 'article')<div class="article-meta">@if($item->author_name)<p>بقلم: {{ $item->author_name }}</p>@endif @if($item->medical_reviewer)<p>المراجعة الطبية: {{ $item->medical_reviewer }}</p>@endif @if($item->published_at)<p>نُشر في <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('Y/m/d') }}</time></p>@endif @if($item->reviewed_at)<p>آخر مراجعة طبية: {{ $item->reviewed_at->format('Y/m/d') }}</p>@endif</div>@endif
    <div class="prose-content">@if($type === 'service')<h2>عن الخدمة</h2>@endif{!! str($item->content ?? $item->rich_description ?? $item->description ?? '')->sanitizeHtml() !!}</div>
    @if($type === 'article' && $item->sources)<h2>المصادر</h2><ul>@foreach($item->sources as $source)<li><a href="{{ \App\Support\ContentUrl::resolve($source['url'] ?? '') }}" rel="noopener" target="_blank">{{ $source['title'] ?? $source['url'] }}</a></li>@endforeach</ul>@endif
@else
    <p>{{ $item->description }}</p><h2>العنوان والوصول</h2><p>{{ $item->address }}</p><p>{{ $item->directions }}</p>
    @if($item->phone)<p><a href="tel:{{ preg_replace('/[^+0-9]/','',$item->phone) }}" dir="ltr">{{ $item->phone }}</a></p>@endif
    @if($item->working_hours)<h2>مواعيد العمل</h2><p>{{ $item->working_hours }}</p>@endif
    @if($item->google_maps_url)<a class="button outline" href="{{ \App\Support\ContentUrl::resolve($item->google_maps_url) }}" target="_blank" rel="noopener">افتحي الخريطة</a>@endif
    @php($branchServices = $services->whereIn('id', $item->service_ids ?? []))
    @if($branchServices->isNotEmpty())<h2>الخدمات المتاحة في هذا الفرع</h2><ul>@foreach($branchServices as $service)<li><a href="{{ route('services.show',$service->slug) }}">{{ $service->name }}</a></li>@endforeach</ul>@endif
@endif
</article><aside class="related-panel"><span class="eyebrow">تابعي التعرّف على العيادة</span>
@if($type === 'service')
    <h2>الفروع التي تقدم الخدمة</h2>
    @forelse($branches->filter(fn($branch)=>in_array($item->id,$branch->service_ids ?? [])) as $branch)<a href="{{ route('branches.show',$branch->slug) }}">{{ $branch->name }}</a>@empty<p>تواصلي مع فريق العيادة لتحديد الفرع المناسب.</p>@endforelse
    @php($relatedArticles = $articles->filter(fn($article)=>in_array($item->id,$article->service_ids ?? [])))
    @if($relatedArticles->isNotEmpty())<h2>مقالات مرتبطة</h2>@foreach($relatedArticles as $article)<a href="{{ route('blog.show',$article->slug) }}">{{ $article->title }}</a>@endforeach @endif
@else
    @php($relatedServices = $services->filter(fn($service)=>$type !== 'article' || in_array($service->id,$item->service_ids ?? []))->take(6))
    @if($relatedServices->isNotEmpty())<h2>خدمات العيادة</h2>@foreach($relatedServices as $service)<a href="{{ route('services.show',$service->slug) }}">{{ $service->name }}</a>@endforeach @endif
@endif
<a class="button" href="{{ \App\Models\SitePage::where('page_key','appointment')->first()?->url }}">طلب موعد</a></aside></div>
