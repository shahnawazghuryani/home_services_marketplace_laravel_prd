<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Services\AI\MarketplaceAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AiController extends Controller
{
    public function __construct(
        private readonly MarketplaceAiService $ai,
    ) {
    }

    public function smartSearch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'problem' => ['required', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            return response()->json([
                'data' => $this->ai->smartSearch($data['problem'], $data['location'] ?? null),
            ]);
        } catch (RuntimeException $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function bookingHelper(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_title' => ['required', 'string', 'max:255'],
            'problem' => ['required', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:255'],
            'preferred_time' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            return response()->json([
                'data' => $this->ai->bookingAssistant($data),
            ]);
        } catch (RuntimeException $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function providerRecommendations(Request $request): JsonResponse
    {
        $data = $request->validate([
            'problem' => ['required', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'category_slug' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $providers = Provider::query()
            ->with(['user', 'services.category'])
            ->whereNotNull('approved_at')
            ->when($data['location'] ?? null, function ($query, $location) {
                $query->where(function ($inner) use ($location) {
                    $inner->where('service_area', 'like', "%{$location}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('city', 'like', "%{$location}%")
                            ->orWhere('address', 'like', "%{$location}%"));
                });
            })
            ->latest()
            ->take((int) config('ai.features.provider_recommendations.max_candidates'))
            ->get();

        $providerPayload = $providers->map(function (Provider $provider) {
            return [
                'id' => $provider->id,
                'name' => $provider->user->name,
                'city' => $provider->user->city,
                'service_area' => $provider->service_area,
                'experience_years' => $provider->experience_years,
                'hourly_rate' => (float) $provider->hourly_rate,
                'availability' => $provider->availability,
                'services' => $provider->services->map(fn ($service) => [
                    'title' => $service->title,
                    'category' => $service->category?->name,
                    'category_slug' => $service->category?->slug,
                    'price' => (float) $service->price,
                ])->values()->all(),
            ];
        })->values()->all();

        try {
            $aiResponse = $this->ai->providerRecommendations($data, $providerPayload);
        } catch (RuntimeException $exception) {
            return $this->errorResponse($exception);
        }

        $reasons = collect($aiResponse['reasons'] ?? [])->keyBy('provider_id');

        $recommendedProviders = $providers
            ->sortBy(fn (Provider $provider) => array_search($provider->id, $aiResponse['recommended_provider_ids'] ?? [], true))
            ->filter(fn (Provider $provider) => in_array($provider->id, $aiResponse['recommended_provider_ids'] ?? [], true))
            ->values()
            ->map(function (Provider $provider) use ($reasons) {
                return [
                    'id' => $provider->id,
                    'name' => $provider->user->name,
                    'phone' => $provider->user->phone,
                    'city' => $provider->user->city,
                    'service_area' => $provider->service_area,
                    'hourly_rate' => (float) $provider->hourly_rate,
                    'reason' => $reasons->get($provider->id)['reason'] ?? 'Recommended by AI.',
                ];
            })
            ->all();

        return response()->json([
            'data' => [
                'summary' => $aiResponse['summary'] ?? 'Recommended providers generated successfully.',
                'providers' => $recommendedProviders,
            ],
        ]);
    }

    private function errorResponse(RuntimeException $exception): JsonResponse
    {
        return response()->json([
            'message' => $this->friendlyMessage($exception->getMessage()),
        ], 503);
    }

    private function friendlyMessage(string $message): string
    {
        $normalized = strtolower($message);

        if (str_contains($normalized, 'quota') || str_contains($normalized, 'billing')) {
            return 'AI helper temporarily unavailable hai. Please thori der baad dobara try karein.';
        }

        if (str_contains($normalized, 'api key')) {
            return 'AI helper configuration incomplete hai. Please administrator se rabta karein.';
        }

        if (str_contains($normalized, 'not supported')) {
            return 'AI helper abhi temporary issue face kar raha hai. Please dobara try karein.';
        }

        return 'AI helper abhi unavailable hai. Please thori der baad dobara try karein.';
    }
}
