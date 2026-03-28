<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureComingSoonMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.coming_soon') || $this->shouldBypass($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'We are getting things ready. Please check back soon.',
            ], 503);
        }

        return response()
            ->view('coming-soon', [
                'title' => 'Coming Soon',
            ], 503)
            ->header('Retry-After', '3600');
    }

    protected function shouldBypass(Request $request): bool
    {
        return $request->is('_setup/run');
    }
}
