<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\Service;
use App\Models\SitePage;
use App\Services\SiteView;
use App\Services\ThemeDraftService;
use Illuminate\Http\Request;

class PageController extends Controller
{
    private function render(Request $request, SitePage $page, array $extra = [])
    {
        abort_if($request->has('preview') && ! $request->hasValidSignature(), 403);
        $page = SiteView::draft($page);
        abort_unless($page->is_published || SiteView::preview(), 404);
        request()->attributes->set('site_page', $page);

        return app(HomeController::class)->renderSite($request, app(ThemeDraftService::class), $page, $extra);
    }

    public function page(Request $request, string $slug)
    {
        $page = SitePage::where('slug', $slug)->whereNotIn('page_key', ['home', 'services', 'articles', 'branches'])->firstOrFail();

        return $this->render($request, $page);
    }

    public function preview(Request $request, SitePage $page)
    {
        abort_unless($request->hasValidSignature() && $request->boolean('preview'), 403);

        return $this->render($request, $page);
    }

    public function services(Request $request)
    {
        return $this->render($request, SitePage::where('page_key', 'services')->firstOrFail());
    }

    public function blogIndex(Request $request)
    {
        abort_if($request->has('page') && (! ctype_digit((string) $request->query('page')) || (int) $request->query('page') < 1), 404);
        $articles = Article::where('is_published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->latest('published_at')->paginate(12);
        abort_if($articles->currentPage() > $articles->lastPage(), 404);

        return $this->render($request, SitePage::where('page_key', 'articles')->firstOrFail(), ['articles' => $articles]);
    }

    public function branches(Request $request)
    {
        return $this->render($request, SitePage::where('page_key', 'branches')->firstOrFail());
    }

    private function detail(Request $request, $item, string $type)
    {
        $title = $item->title ?? $item->name;
        $page = new SitePage(['page_key' => $type, 'title' => $title, 'sections' => [], 'is_published' => true, 'robots' => 'index_follow']);
        request()->attributes->set('content_item', $item);

        return $this->render($request, $page, ['item' => $item, 'type' => $type]);
    }

    public function serviceShow(Request $request, string $slug)
    {
        return $this->detail($request, Service::where('is_active', true)->where('slug', $slug)->firstOrFail(), 'service');
    }

    public function blogShow(Request $request, string $slug)
    {
        return $this->detail($request, Article::where('is_published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->where('slug', $slug)->firstOrFail(), 'article');
    }

    public function branchShow(Request $request, string $slug)
    {
        return $this->detail($request, ClinicBranch::where('is_active', true)->where('slug', $slug)->firstOrFail(), 'branch');
    }
}
