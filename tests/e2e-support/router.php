<?php

use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\PageSection;
use App\Models\Service;
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
    Route::get('/__e2e/preview', fn () => response()->json(['url' => URL::temporarySignedRoute('home', now()->addMinutes(10), ['preview' => 1, 'theme' => request('theme', 'helal_classic')])]));
});
$app->handleRequest(Request::capture());
