<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventAdsIndexing
{
    public const HEADER = 'noindex, nofollow, noarchive, nosnippet, noimageindex';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', self::HEADER);

        return $response;
    }
}
