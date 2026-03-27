<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::withCount('services')->get();
        $servicesQuery = Service::with(['category', 'categories', 'provider.user'])
            ->where('is_active', true);

        if ($request->filled('category')) {
            $servicesQuery->whereHas('categories', fn ($categoryQuery) => $categoryQuery->where('slug', $request->input('category')));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $servicesQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhereHas('provider.user', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('location')) {
            $location = $request->input('location');
            $servicesQuery->whereHas('provider', function ($providerQuery) use ($location) {
                $providerQuery->where('service_area', 'like', "%{$location}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('city', 'like', "%{$location}%")
                        ->orWhere('address', 'like', "%{$location}%"));
            });
        }

        $featuredServices = $servicesQuery->latest()->take(9)->get();

        $featuredProviders = Provider::with(['user', 'services.category', 'services.categories'])
            ->whereNotNull('approved_at')
            ->when($request->filled('location'), function ($query) use ($request) {
                $location = $request->input('location');
                $query->where(function ($inner) use ($location) {
                    $inner->where('service_area', 'like', "%{$location}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('city', 'like', "%{$location}%")
                            ->orWhere('address', 'like', "%{$location}%"));
                });
            })
            ->latest()
            ->take(6)
            ->get();
        $stats = [
            'services' => Service::count(),
            'providers' => Provider::count(),
            'bookings' => \App\Models\Booking::count(),
            'reviews' => \App\Models\Review::count(),
        ];

        return view('home', [
            'categories' => $categories,
            'featuredServices' => $featuredServices,
            'featuredProviders' => $featuredProviders,
            'stats' => $stats,
            'locationSuggestions' => $this->locationSuggestions(),
            'filters' => $request->only(['search', 'location', 'category']),
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
