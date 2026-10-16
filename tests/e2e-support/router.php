<?php

use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\SiteNavigation;
use App\Models\SitePage;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\ThemeRevision;
use App\Models\User;
use App\Models\Video;
use App\Support\ClinicFeatures;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

$root = dirname(__DIR__, 2);
if (getenv('APP_ENV') !== 'testing' || getenv('CLINIC_E2E') !== '1') {
    http_response_code(404);
    exit;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/storage/')) {
    $base = realpath(getenv('LARAVEL_STORAGE_PATH').'/app/public');
    $asset = realpath($base.'/'.substr($path, 9));
    if ($asset && str_starts_with($asset, $base.DIRECTORY_SEPARATOR) && is_file($asset)) {
        header('Content-Type: '.mime_content_type($asset));
        readfile($asset);
        exit;
    }
    http_response_code(404);
    exit;
}
$file = realpath($root.'/public'.$path);
if ($file && str_starts_with($file, realpath($root.'/public').DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->instance('request', Request::capture());
$app->make(Kernel::class)->bootstrap();
config(['filesystems.disks.tmp-for-tests' => ['driver' => 'local', 'root' => storage_path('app/e2e-uploads'), 'throw' => true]]);
Route::middleware('web')->group(function (): void {
    Route::get('/__e2e/reset', function () {
        Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);
        foreach (['admin' => 'super_admin', 'visitor' => null] as $name => $role) {
            $user = User::create(['email' => $name.'@example.test', 'name' => 'E2E '.$name, 'role' => $name === 'admin' ? 'admin' : 'patient', 'password' => 'E2E-Only-Password-123!']);
            if ($role) {
                $user->assignRole($role);
            }
        }
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return response()->json(['reset' => true]);
    });
    Route::get('/__e2e/state', function () {
        return response()->json([
            'pages' => SitePage::all(), 'menus' => SiteNavigation::all(),
            'theme' => SiteSetting::where('key', 'active_theme')->value('value'),
            'sections' => PageSection::orderBy('order')->get(['id', 'section_key', 'body', 'draft_data', 'is_active', 'order']),
            'revisions' => ThemeRevision::count(),
            'bookings' => DB::table('bookings')->get(),
            'services' => Service::all(), 'articles' => Article::all(),
            'videos' => Video::all(), 'branches' => ClinicBranch::all(),
            'testimonials' => Testimonial::all(),
            'full' => ClinicFeatures::enabled('Booking'),
        ]);
    });
    Route::get('/__e2e/audit-theme', function () {
        abort_unless(in_array(request('theme'), ['helal_classic', 'helal_modern']), 422);
        SiteSetting::where('key', 'active_theme')->update(['value' => request('theme')]);

        return response()->json(['ok' => true]);
    });
    Route::get('/__e2e/multipage', function () {
        $article = Article::first();
        $article->update(['is_published' => true, 'published_at' => now()->subDay(), 'author_name' => 'كاتب اختبار', 'medical_reviewer' => 'مراجع اختبار', 'reviewed_at' => now(), 'sources' => [['title' => 'مرجع اختبار', 'url' => 'https://example.org/reference']], 'service_ids' => [Service::first()->id]]);
        ClinicBranch::first()->update(['service_ids' => [Service::first()->id]]);

        return response()->json(['article' => '/blog/'.$article->slug, 'service' => '/services/'.Service::first()->slug, 'branch' => '/branches/'.ClinicBranch::first()->slug]);
    });
    Route::get('/__e2e/preview', function () {
        if (request('kind')) {
            [$route,$model] = match (request('kind')) {
                'service' => ['services.show', Service::first()],'article' => ['blog.show', Article::where('is_published', true)->first()],default => ['branches.show', ClinicBranch::first()]
            };

            return response()->json(['url' => URL::temporarySignedRoute($route, now()->addMinutes(10), ['slug' => $model->slug, 'preview' => 1, 'theme' => request('theme', 'helal_classic')])]);
        }
        $page = SitePage::where('page_key', request('page', 'home'))->firstOrFail();

        return response()->json(['url' => URL::temporarySignedRoute('site.preview', now()->addMinutes(10), ['page' => $page->id, 'preview' => 1, 'theme' => request('theme', 'helal_classic')])]);
    });
});
$response = $app->make(Kernel::class)->handle(Request::capture());
if ($response->getStatusCode() === 500) {
    $diagnostics = $root.'/.e2e/compiled-views';
    @mkdir($diagnostics, 0700, true);
    foreach (glob(storage_path('framework/views/*.php')) as $compiled) {
        copy($compiled, $diagnostics.'/'.basename($compiled));
    }
}
$response->send();
$app->make(Kernel::class)->terminate(Request::capture(), $response);
