<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpaPageController extends Controller
{
    public function servicesIndex(Request $request): JsonResponse
    {
        $query = Service::with(['category', 'categories', 'provider.user'])
            ->where('is_active', true)
            ->whereHas('provider', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'));

        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->where('slug', $request->input('category')));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhereHas('provider.user', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('location')) {
            $location = $request->input('location');
            $query->where(function ($inner) use ($location) {
                $inner->whereHas('provider', function ($providerQuery) use ($location) {
                    $providerQuery->whereHas('user', fn ($userQuery) => $userQuery
                        ->where('city', 'like', "%{$location}%")
                        ->orWhere('address', 'like', "%{$location}%"));
                });
            });
        }

        $services = $query->latest()->get();
        $providerRatings = Review::query()
            ->selectRaw('provider_id, ROUND(AVG(rating), 1) as rating_avg, COUNT(*) as reviews_count')
            ->groupBy('provider_id')
            ->get()
            ->keyBy('provider_id');

        return response()->json([
            'services' => $services->map(fn (Service $service) => [
                'id' => $service->id,
                'title' => $service->title,
                'slug' => $service->slug,
                'short_description' => $service->short_description,
                'price' => (float) $service->price,
                'duration_minutes' => $service->duration_minutes,
                'image_url' => $this->publicAssetUrl($service->image_path),
                'category' => $service->category?->name,
                'categories' => $service->categories->pluck('name')->values()->all(),
                'provider' => [
                    'id' => $service->provider->id,
                    'name' => $service->provider->user->name,
                    'phone' => $service->provider->user->phone,
                    'city' => $service->provider->user->city,
                    'rating_avg' => (float) ($providerRatings->get($service->provider->user_id)->rating_avg ?? 0),
                    'reviews_count' => (int) ($providerRatings->get($service->provider->user_id)->reviews_count ?? 0),
                ],
            ]),
            'categories' => Category::query()
                ->whereHas('services', function ($categoryQuery) {
                    $categoryQuery->where('is_active', true)
                        ->whereHas('provider', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'));
                })
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'location_suggestions' => $this->locationSuggestions(),
            'maps' => [
                'googlePlacesEnabled' => filled(config('services.google_maps.browser_key')),
                'googlePlacesApiKey' => config('services.google_maps.browser_key'),
            ],
            'filters' => $request->only(['search', 'location', 'category']),
            'summary' => [
                'results' => $services->count(),
                'search' => $request->input('search'),
                'location' => $request->input('location'),
                'category' => $request->input('category'),
            ],
        ]);
    }

    public function serviceShow(Request $request, string $slug): JsonResponse
    {
        $service = Service::with(['category', 'categories', 'provider.user', 'bookings.reviews'])
            ->whereHas('provider', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'))
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedCategoryIds = $service->categories->pluck('id')->all();
        if ($relatedCategoryIds === []) {
            $relatedCategoryIds = [$service->category_id];
        }

        $relatedServices = Service::with(['category', 'categories', 'provider.user'])
            ->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('categories.id', $relatedCategoryIds))
            ->whereHas('provider', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'))
            ->where('id', '!=', $service->id)
            ->take(3)
            ->get();

        $providerLocationLabel = $this->providerLocationLabel($service);
        $serviceImageUrl = $this->publicAssetUrl($service->image_path);
        $fallbackCoverImageUrl = $serviceImageUrl
            ?? $this->publicAssetUrl(
                Service::query()
                    ->where('provider_id', $service->provider_id)
                    ->whereNotNull('image_path')
                    ->where('image_path', '!=', '')
                    ->latest('id')
                    ->value('image_path')
            );

        return response()->json([
            'service' => [
                'id' => $service->id,
                'title' => $service->title,
                'slug' => $service->slug,
                'short_description' => $service->short_description,
                'description' => $service->description,
                'price' => (float) $service->price,
                'price_type' => $service->price_type,
                'duration_minutes' => $service->duration_minutes,
                'image_url' => $serviceImageUrl,
                'category' => $service->category?->name,
                'categories' => $service->categories->pluck('name')->values()->all(),
                'provider' => [
                    'id' => $service->provider->id,
                    'name' => $service->provider->user->name,
                    'phone' => $service->provider->user->phone,
                    'city' => $service->provider->user->city,
                ],
            ],
            'cover_image_url' => $fallbackCoverImageUrl,
            'provider_location_label' => $providerLocationLabel,
            'provider_map_url' => $this->googleMapEmbedUrl($providerLocationLabel),
            'provider_map_search_url' => $this->googleMapSearchUrl($providerLocationLabel),
            'related_services' => $relatedServices->map(fn (Service $related) => [
                'id' => $related->id,
                'title' => $related->title,
                'slug' => $related->slug,
                'provider_name' => $related->provider->user->name,
                'categories' => $related->categories->pluck('name')->values()->all(),
            ]),
            'auth' => [
                'logged_in' => (bool) $request->user(),
                'role' => $request->user()?->role,
                'can_book' => (bool) $request->user()?->isCustomer(),
            ],
        ]);
    }

    public function providerShow(int $provider): JsonResponse
    {
        $provider = Provider::with(['user', 'services.category', 'services.categories', 'reviews.customer'])
            ->whereNotNull('approved_at')
            ->findOrFail($provider);
        $services = $provider->services
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'title' => $service->title,
                'slug' => $service->slug,
                'price' => (float) $service->price,
                'category' => $service->category?->name,
                'categories' => $service->categories->pluck('name')->values()->all(),
                'image_url' => $this->publicAssetUrl($service->image_path),
            ])
            ->values();
        $coverImageUrl = ($services->first(fn (array $service) => filled($service['image_url'])) ?? [])['image_url'] ?? null;

        $locationLabel = collect([
            $provider->user->city,
            $provider->user->address,
        ])->filter()->implode(', ');

        return response()->json([
            'provider' => [
                'id' => $provider->id,
                'name' => $provider->user->name,
                'phone' => $provider->user->phone,
                'city' => $provider->user->city,
                'address' => $provider->user->address,
                'bio' => $provider->bio,
                'experience_years' => $provider->experience_years,
                'hourly_rate' => (float) $provider->hourly_rate,
                'availability' => $provider->availability,
                'approved' => (bool) $provider->approved_at,
            ],
            'cover_image_url' => $coverImageUrl,
            'location_label' => $locationLabel,
            'provider_map_url' => $this->googleMapEmbedUrl($locationLabel),
            'provider_map_search_url' => $this->googleMapSearchUrl($locationLabel),
            'services' => $services,
            'reviews' => $provider->reviews
                ->sortByDesc('id')
                ->take(6)
                ->map(fn ($review) => [
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'customer_name' => $review->customer?->name ?? 'Customer',
                ])
                ->values(),
        ]);
    }

    public function bookingCreate(Request $request, string $slug): JsonResponse
    {
        $service = Service::with(['category', 'categories', 'provider.user'])
            ->where('is_active', true)
            ->whereHas('provider', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'))
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($request->user()?->isCustomer(), 403);

        return response()->json([
            'service' => [
                'id' => $service->id,
                'title' => $service->title,
                'slug' => $service->slug,
                'short_description' => $service->short_description,
                'price' => (float) $service->price,
                'duration_minutes' => $service->duration_minutes,
                'provider_name' => $service->provider->user->name,
                'provider_phone' => $service->provider->user->phone,
            ],
            'customer' => [
                'address' => $request->user()->address,
            ],
            'payment_methods' => ['Cash on Service', 'EasyPaisa', 'JazzCash'],
        ]);
    }

    protected function providerLocationLabel(Service $service): string
    {
        $parts = array_filter([
            $service->provider->user->city,
            $service->provider->user->address,
        ]);

        return implode(', ', $parts);
    }

    protected function googleMapEmbedUrl(string $location): string
    {
        return 'https://www.google.com/maps?q=' . urlencode($location) . '&output=embed';
    }

    protected function googleMapSearchUrl(string $location): string
    {
        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($location);
    }

    protected function publicAssetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $raw = trim((string) $path);
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            $raw = (string) (parse_url($raw, PHP_URL_PATH) ?? '');
        }

        if (str_starts_with($raw, '/media/')) {
            return $raw;
        }

        $normalizedPath = trim($raw, '/');

        return $normalizedPath === ''
            ? null
            : '/media/' . implode('/', array_map('rawurlencode', explode('/', $normalizedPath)));
    }

    protected function locationSuggestions(): array
    {
        return collect()
            ->merge(User::query()
                ->whereHas('providerProfile', fn ($providerQuery) => $providerQuery
                    ->whereNotNull('approved_at')
                    ->whereHas('services', fn ($serviceQuery) => $serviceQuery->where('is_active', true)))
                ->whereNotNull('city')
                ->pluck('city'))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->sort()
            ->take(20)
            ->values()
            ->all();
    }
}

