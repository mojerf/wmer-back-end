<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets browsers and CDNs reuse public content (the same for every visitor) for a short while.
 * Only successful GET responses are marked; the server-side ContentCache still bumps instantly on edits,
 * so a change reaches visitors within the max-age below.
 */
class PublicCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $response->headers->set('Cache-Control', 'public, max-age=30, stale-while-revalidate=120');
        }

        return $response;
    }
}
