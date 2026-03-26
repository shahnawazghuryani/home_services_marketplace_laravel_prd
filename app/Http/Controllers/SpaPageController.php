<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpaPageController extends Controller
{
    public function servicesIndex(Request $request): JsonResponse
    {
        $query = Service::with(['category', 'provider.user'])->where('is_active', true);

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $request->input('category')));
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
                    $providerQuery->where('service_area', 'like', "%{$location}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('city', 'like', "%{$location}%")
                            ->orWhere('address', 'like', "%{$location}%"));
                });
            });
        }

        $services = $query->latest()->get();

        return response()->json([
            'services' => $services->map(fn (Service $service) => [
                'id' => $service->id,
                'title' => $service->title,
                'slug' => $service->slug,
                'short_description' => $service->short_description,
                'price' => (float) $service->price,
                'duration_minutes' => $service->duration_minutes,
                'image_url' => $service->image_path ? asset($service->image_path) : null,
                'category' => $service->category->name,
                'provider' => [
                    'id' => $service->provider->id,
                    'name' => $service->provider->user->name,
                    'phone' => $service->provider->user->phone,
                    'city' => $service->provider->user->city,
                    'service_area' => $service->provider->service_area,
                ],
            ]),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug']),
            'location_suggestions' => $this->locationSuggestions(),
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
        $service = Service::with(['category', 'provider.user', 'bookings.reviews'])
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedServices = Service::with(['category', 'provider.user'])
            ->where('category_id', $service->category_id)
            ->where('id', '!=', $service->id)
            ->take(3)
            ->get();

        $providerLocationLabel = $this->providerLocationLabel($service);

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
                'image_url' => $service->image_path ? asset($service->image_path) : null,
                'category' => $service->category->name,
                'provider' => [
                    'id' => $service->provider->id,
                    'name' => $service->provider->user->name,
                    'phone' => $service->provider->user->phone,
                    'city' => $service->provider->user->city,
                    'service_area' => $service->provider->service_area,
                ],
            ],
            'provider_location_label' => $providerLocationLabel,
            'provider_map_url' => $this->googleMapEmbedUrl($providerLocationLabel),
            'provider_map_search_url' => $this->googleMapSearchUrl($providerLocationLabel),
            'related_services' => $relatedServices->map(fn (Service $related) => [
                'id' => $related->id,
                'title' => $related->title,
                'slug' => $related->slug,
                'provider_name' => $related->provider->user->name,
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
        $provider = Provider::with(['user', 'services.category', 'reviews.customer'])
            ->findOrFail($provider);

        $locationLabel = collect([
            $provider->service_area,
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
                'service_area' => $provider->service_area,
                'bio' => $provider->bio,
                'experience_years' => $provider->experience_years,
                'hourly_rate' => (float) $provider->hourly_rate,
                'availability' => $provider->availability,
                'approved' => (bool) $provider->approved_at,
            ],
            'location_label' => $locationLabel,
            'provider_map_url' => $this->googleMapEmbedUrl($locationLabel),
            'provider_map_search_url' => $this->googleMapSearchUrl($locationLabel),
            'services' => $provider->services->map(fn (Service $service) => [
                'id' => $service->id,
                'title' => $service->title,
                'slug' => $service->slug,
                'price' => (float) $service->price,
                'category' => $service->category?->name,
            ]),
        ]);
    }

    public function bookingCreate(Request $request, string $slug): JsonResponse
    {
        $service = Service::with(['category', 'provider.user'])->where('slug', $slug)->firstOrFail();

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
            $service->provider->service_area,
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

    protected function locationSuggestions(): array
    {
        return collect()
            ->merge(User::query()->whereNotNull('city')->pluck('city'))
            ->merge(User::query()->whereNotNull('address')->pluck('address'))
            ->merge(Provider::query()->whereNotNull('service_area')->pluck('service_area'))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->sort()
            ->take(20)
            ->values()
            ->all();
    }
}
