<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index(Request $request)
    {
        $servicesQuery = Service::with(['category', 'provider.user'])
            ->where('is_active', true);

        if ($request->filled('category')) {
            $servicesQuery->whereHas('category', fn ($query) => $query->where('slug', $request->string('category')));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $servicesQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhereHas('provider.user', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('location')) {
            $location = $request->string('location')->toString();
            $servicesQuery->whereHas('provider', function ($providerQuery) use ($location) {
                $providerQuery->where('service_area', 'like', "%{$location}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('city', 'like', "%{$location}%")
                        ->orWhere('address', 'like', "%{$location}%"));
            });
        }

        $providersQuery = Provider::with(['user'])
            ->whereNotNull('approved_at');

        if ($request->filled('location')) {
            $location = $request->string('location')->toString();
            $providersQuery->where(function ($query) use ($location) {
                $query->where('service_area', 'like', "%{$location}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('city', 'like', "%{$location}%")
                        ->orWhere('address', 'like', "%{$location}%"));
            });
        }

        $categories = Category::withCount('services')
            ->with(['services' => fn ($query) => $query->where('is_active', true)->latest()])
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                $categoryImage = $category->services
                    ->first(fn ($service) => !empty($service->image_path));

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'services_count' => $category->services_count,
                    'image_url' => $categoryImage?->image_path ? asset($categoryImage->image_path) : null,
                ];
            });

        $services = $servicesQuery->latest()->take(9)->get()->map(function ($service) {
            return [
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
            ];
        });

        $providers = $providersQuery->latest()->take(6)->get()->map(function ($provider) {
            return [
                'id' => $provider->id,
                'name' => $provider->user->name,
                'phone' => $provider->user->phone,
                'city' => $provider->user->city,
                'service_area' => $provider->service_area,
                'bio' => $provider->bio,
                'experience_years' => $provider->experience_years,
                'hourly_rate' => (float) $provider->hourly_rate,
            ];
        });

        return response()->json([
            'brand' => [
                'name' => 'GharKaam',
                'country' => 'Pakistan',
                'tagline' => 'Trusted home service experts are now easy to find.',
                'description' => 'Search fast, compare verified providers, and book trusted home services from one place.',
            ],
            'stats' => [
                'services' => Service::count(),
                'providers' => Provider::count(),
                'bookings' => \App\Models\Booking::count(),
                'reviews' => \App\Models\Review::count(),
            ],
            'categories' => $categories,
            'services' => $services,
            'providers' => $providers,
            'filters' => $request->only(['search', 'location', 'category']),
            'location_suggestions' => $this->locationSuggestions(),
        ]);
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
