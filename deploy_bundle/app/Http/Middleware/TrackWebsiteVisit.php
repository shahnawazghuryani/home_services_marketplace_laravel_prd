<?php

namespace App\Http\Middleware;

use App\Models\Provider;
use App\Models\Service;
use App\Models\WebsiteVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackWebsiteVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        [$providerId, $serviceId] = $this->resolveTargets($request);

        WebsiteVisit::create([
            'user_id' => $request->user()?->id,
            'provider_id' => $providerId,
            'service_id' => $serviceId,
            'visitor_key' => $this->visitorKey($request),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'referrer_url' => $request->headers->get('referer'),
            'source' => $this->source($request),
            'device_type' => $this->deviceType($request),
            'visited_at' => now(),
        ]);

        return $response;
    }

    protected function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        if (str_starts_with($request->path(), '_debugbar')) {
            return false;
        }

        return true;
    }

    protected function resolveTargets(Request $request): array
    {
        $route = $request->route();
        $providerId = null;
        $serviceId = null;

        if (! $route) {
            return [$providerId, $serviceId];
        }

        $provider = $route->parameter('provider');
        $service = $route->parameter('service');
        $slug = $route->parameter('slug');

        if ($provider instanceof Provider) {
            $providerId = $provider->id;
        }

        if ($service instanceof Service) {
            $serviceId = $service->id;
            $providerId ??= $service->provider_id;
        }

        if ($slug && $request->routeIs('services.show')) {
            $matchedService = Service::query()->select(['id', 'provider_id'])->where('slug', $slug)->first();
            if ($matchedService) {
                $serviceId = $matchedService->id;
                $providerId ??= $matchedService->provider_id;
            }
        }

        return [$providerId, $serviceId];
    }

    protected function visitorKey(Request $request): string
    {
        return sha1(implode('|', [
            $request->ip(),
            substr((string) $request->userAgent(), 0, 120),
            $request->session()->getId(),
        ]));
    }

    protected function source(Request $request): string
    {
        $referer = $request->headers->get('referer');

        if (! $referer) {
            return 'Direct';
        }

        $host = parse_url($referer, PHP_URL_HOST);

        return $host ?: 'Direct';
    }

    protected function deviceType(Request $request): string
    {
        $agent = strtolower((string) $request->userAgent());

        if (str_contains($agent, 'mobile')) {
            return 'mobile';
        }

        if (str_contains($agent, 'tablet') || str_contains($agent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }
}