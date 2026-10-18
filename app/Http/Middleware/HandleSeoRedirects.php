<?php

// app/Http/Middleware/HandleSeoRedirects.php

namespace App\Http\Middleware;

use App\Models\SeoRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class HandleSeoRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim(rawurldecode($request->path()), '/');

        $redirect = in_array($request->method(), ['GET', 'HEAD']) ? SeoRedirect::where('source_url', $path)->where('is_active', true)->first() : null;

        if ($redirect) {
            DB::table('seo_redirects')
                ->where('id', $redirect->id)
                ->increment('hit_count', 1, ['last_accessed_at' => now()]);

            if ((int) $redirect->redirect_type === 410) {
                return response('هذه الصفحة لم تعد متاحة.', 410)->header('X-Robots-Tag', 'noindex');
            }

            return redirect($redirect->target_url, (int) $redirect->redirect_type);
        }

        return $next($request);
    }
}
