<?php

use App\Helpers\SeoHelper;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use App\Models\SitePage;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about-us', [PageController::class, 'page'])->defaults('slug', 'about-us')->name('about');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/services/{slug}', [PageController::class, 'serviceShow'])->name('services.show');
Route::get('/gallery', [PageController::class, 'page'])->defaults('slug', 'gallery')->name('gallery');
Route::get('/contact-us', [PageController::class, 'page'])->defaults('slug', 'contact-us')->name('contact');
Route::get('/blog', [PageController::class, 'blogIndex'])->name('blog.index');
Route::get('/blog/{slug}', [PageController::class, 'blogShow'])->name('blog.show');
Route::post('/bookings', [HomeController::class, 'storeBooking'])->middleware('throttle:10,1')->name('bookings.store');
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap-{part}.xml', [SitemapController::class, 'index'])->whereNumber('part');

Route::get('/branches', [PageController::class, 'branches'])->name('branches');
Route::get('/branches/{slug}', [PageController::class, 'branchShow'])->name('branches.show');
Route::get('/site-preview/{page}', [PageController::class, 'preview'])->name('site.preview')->middleware('signed');
Route::get('/robots.txt', function () {
    $body = "User-agent: *\nDisallow: /codeAiAdminDash\nDisallow: /site-preview/\nDisallow: /patient/\nDisallow: /patient-auth/\nDisallow: /*?*preview=\n";
    $body .= trim(SeoHelper::getSetting('robots_extra') ?? '')."\n";
    if (in_array(SeoHelper::getSetting('sitemap_enabled'), ['1', 1, true, 'true'], true)) {
        $body .= 'Sitemap: '.url('/sitemap.xml')."\n";
    }

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});
Route::get('/{slug}', [PageController::class, 'page'])->where('slug', '(?!(?i:'.implode('|', array_map(fn ($slug) => preg_quote($slug, '~'), SitePage::RESERVED_SLUGS)).')$)[^/]+')->name('site.page');
