<?php

// app/Http/Middleware/Track404Middleware.php

namespace App\Http\Middleware;

use App\Models\Seo404Monitor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Track404Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $status = method_exists($response, 'status') ? $response->status() : $response->getStatusCode();

        if ($status === 404 && ! $request->expectsJson()) {
            $url = '/'.ltrim($request->getPathInfo(), '/');

            $monitor = Seo404Monitor::firstOrNew(['url' => $url]);
            $monitor->hit_count++;
            $monitor->referer_url = $request->header('referer');
            $monitor->user_agent = $request->userAgent();
            $monitor->ip_address = $request->ip();
            $monitor->last_seen_at = now();
            if (! $monitor->exists) {
                $monitor->first_seen_at = now();
            }
            $monitor->save();
        }

        return $response;
    }
}
