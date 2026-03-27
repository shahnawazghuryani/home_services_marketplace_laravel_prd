<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Category;
use App\Models\MarketplaceNotification;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\WebsiteVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notificationsFeed()->latest()->take(6)->get();

        if ($user->isAdmin()) {
            $stats = [
                'users' => User::count(),
                'providers' => Provider::count(),
                'bookings' => Booking::count(),
                'revenue' => \App\Models\Payment::where('status', 'paid')->sum('amount'),
                'visits' => WebsiteVisit::count(),
                'unique_visitors' => WebsiteVisit::query()->distinct('visitor_key')->count('visitor_key'),
            ];

            return view('dashboard.admin', [
                'stats' => $stats,
                'providers' => Provider::with('user')->latest()->get(),
                'bookings' => Booking::with(['customer', 'provider', 'service'])->latest()->take(8)->get(),
                'notifications' => $notifications,
                'categories' => Category::latest()->get(),
                'services' => Service::with(['category', 'categories', 'provider.user'])->latest()->take(12)->get(),
                'trafficSummary' => $this->adminTrafficSummary(),
            ]);
        }

        if ($user->isProvider()) {
            $servicesQuery = Service::with('category')->where('provider_id', $user->providerProfile->id);

            if ($request->filled('service_search')) {
                $search = $request->input('service_search');
                $servicesQuery->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('service_status')) {
                $servicesQuery->where('is_active', $request->input('service_status') === 'active');
            }

            return view('dashboard.provider', [
                'profile' => $user->providerProfile,
                'services' => $servicesQuery->latest()->get(),
                'bookings' => Booking::with(['customer', 'service'])->where('provider_id', $user->id)->latest()->get(),
                'notifications' => $notifications,
                'trafficSummary' => $this->providerTrafficSummary($user->providerProfile?->id),
            ]);
        }

        return view('dashboard.customer', [
            'bookings' => Booking::with(['provider', 'service', 'reviews'])->where('customer_id', $user->id)->latest()->get(),
            'categories' => Category::withCount('services')->get(),
            'notifications' => $notifications,
            'trafficSummary' => $this->customerTrafficSummary($user->id),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notificationsFeed()->latest()->take(6)->get()->map(fn ($notification) => [
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
        ]);

        if ($user->isAdmin()) {
            $stats = [
                'users' => User::count(),
                'providers' => Provider::count(),
                'bookings' => Booking::count(),
                'revenue' => (float) \App\Models\Payment::where('status', 'paid')->sum('amount'),
                'visits' => WebsiteVisit::count(),
                'unique_visitors' => WebsiteVisit::query()->distinct('visitor_key')->count('visitor_key'),
            ];

            return response()->json([
                'role' => 'admin',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'notifications' => $notifications,
                'stats' => $stats,
                'trafficSummary' => [
                    'today' => $this->adminTrafficSummary()['today'],
                    'top_sources' => $this->adminTrafficSummary()['top_sources']->map(fn ($item) => [
                        'label' => $item->source ?? 'Direct',
                        'total' => $item->total,
                    ]),
                    'top_pages' => $this->adminTrafficSummary()['top_pages']->map(fn ($item) => [
                        'label' => '/' . $item->path,
                        'total' => $item->total,
                    ]),
                    'latest_visits' => $this->adminTrafficSummary()['latest_visits']->map(fn ($visit) => [
                        'source' => $visit->source,
                        'path' => '/' . $visit->path,
                        'device_type' => $visit->device_type,
                        'visited_at' => optional($visit->visited_at)->format('d M Y, h:i A'),
                    ]),
                ],
                'providers' => Provider::with('user')->latest()->get()->map(fn ($provider) => [
                    'id' => $provider->id,
                    'name' => $provider->user->name,
                    'service_area' => $provider->service_area,
                    'approved' => (bool) $provider->approved_at,
                    'approval_url' => route('admin.providers.approve', $provider),
                ]),
                'adminCategories' => Category::latest()->get()->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'description' => $category->description,
                    'update_url' => route('admin.categories.update', $category),
                    'delete_url' => route('admin.categories.destroy', $category),
                ]),
                'services' => Service::with(['category', 'categories', 'provider.user'])->latest()->take(12)->get()->map(fn ($service) => [
                    'id' => $service->id,
                    'title' => $service->title,
                    'provider' => $service->provider->user->name,
                    'category' => $service->categories->pluck('name')->implode(', '),
                    'status' => $service->is_active ? 'active' : 'inactive',
                    'edit_url' => route('admin.services.edit', $service),
                    'delete_url' => route('admin.services.destroy', $service),
                ]),
                'bookings' => Booking::with(['customer', 'provider', 'service'])->latest()->take(8)->get()->map(fn ($booking) => [
                    'customer' => $booking->customer->name,
                    'provider' => $booking->provider->name,
                    'service' => $booking->service->title,
                    'status' => $booking->status,
                ]),
            ]);
        }

        if ($user->isProvider()) {
            $profile = $user->providerProfile;
            $services = Service::with(['category', 'categories'])->where('provider_id', $profile?->id)->latest()->get();
            $bookings = Booking::with(['customer', 'service'])->where('provider_id', $user->id)->latest()->get();
            $trafficSummary = $this->providerTrafficSummary($profile?->id);

            return response()->json([
                'role' => 'provider',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'notifications' => $notifications,
                'profile' => [
                    'approved' => (bool) $profile?->approved_at,
                    'service_area' => $profile?->service_area,
                    'availability' => $profile?->availability,
                    'hourly_rate' => (float) ($profile?->hourly_rate ?? 0),
                ],
                'stats' => [
                    'services' => $services->count(),
                    'bookings' => $bookings->count(),
                    'approved' => $profile?->approved_at ? 'Yes' : 'No',
                    'area' => $profile?->service_area,
                    'total_views' => $trafficSummary['total_views'],
                    'today_views' => $trafficSummary['today_views'],
                ],
                'trafficSummary' => [
                    'top_sources' => collect($trafficSummary['top_sources'])->map(fn ($item) => [
                        'label' => $item->source ?? 'Direct',
                        'total' => $item->total,
                    ])->values(),
                ],
                'actions' => [
                    'edit_profile_url' => route('provider.profile.edit'),
                    'add_service_url' => route('provider.services.create'),
                ],
                'services' => $services->map(fn ($service) => [
                    'id' => $service->id,
                    'title' => $service->title,
                    'category' => $service->categories->pluck('name')->implode(', '),
                    'price' => (float) $service->price,
                    'duration_minutes' => $service->duration_minutes,
                    'status' => $service->is_active ? 'active' : 'inactive',
                    'edit_url' => route('provider.services.edit', $service),
                    'delete_url' => route('provider.services.destroy', $service),
                ]),
                'bookings' => $bookings->map(fn ($booking) => [
                    'id' => $booking->id,
                    'customer' => $booking->customer->name,
                    'service' => $booking->service->title,
                    'schedule' => optional($booking->scheduled_at)->format('d M Y, h:i A'),
                    'status' => $booking->status,
                    'status_url' => route('bookings.status', $booking),
                ]),
            ]);
        }

        $bookings = Booking::with(['provider', 'service', 'reviews'])->where('customer_id', $user->id)->latest()->get();

        return response()->json([
            'role' => 'customer',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'notifications' => $notifications,
            'stats' => [
                'total_bookings' => $bookings->count(),
                'completed' => $bookings->where('status', 'completed')->count(),
                'pending' => $bookings->where('status', 'pending')->count(),
                'reviews' => $bookings->filter(fn ($booking) => $booking->reviews->count() > 0)->count(),
            ],
            'trafficSummary' => $this->customerTrafficSummary($user->id),
            'categories' => Category::withCount('services')->get()->map(fn ($category) => [
                'name' => $category->name,
                'count' => $category->services_count,
                'url' => route('home', ['category' => $category->slug]) . '#services',
            ]),
            'bookings' => $bookings->map(fn ($booking) => [
                'id' => $booking->id,
                'service' => $booking->service->title,
                'provider' => $booking->provider->name,
                'schedule' => optional($booking->scheduled_at)->format('d M Y, h:i A'),
                'status' => $booking->status,
                'cancel_url' => route('bookings.status', $booking),
                'review_url' => route('bookings.reviews.store', $booking),
                'has_review' => $booking->reviews->count() > 0,
            ]),
        ]);
    }

    public function editProviderProfile(Request $request)
    {
        abort_unless($request->user()->isProvider(), 403);

        return view('dashboard.provider-profile', [
            'profile' => $request->user()->providerProfile,
            'user' => $request->user(),
        ]);
    }

    public function providerProfileData(Request $request): JsonResponse
    {
        abort_unless($request->user()->isProvider(), 403);

        $profile = $request->user()->providerProfile;

        return response()->json([
            'user' => [
                'name' => $request->user()->name,
                'phone' => $request->user()->phone,
                'city' => $request->user()->city,
                'address' => $request->user()->address,
            ],
            'profile' => [
                'bio' => $profile->bio,
                'experience_years' => $profile->experience_years,
                'hourly_rate' => (float) $profile->hourly_rate,
                'service_area' => $profile->service_area,
                'availability' => $profile->availability,
            ],
        ]);
    }

    public function updateProviderProfile(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isProvider(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string', 'max:2000'],
            'experience_years' => ['required', 'integer', 'min:0'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'service_area' => ['required', 'string', 'max:255'],
            'availability' => ['required', 'string', 'max:255'],
        ]);

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'city' => $data['city'],
            'address' => $data['address'],
        ]);

        $user->providerProfile->update([
            'bio' => $data['bio'],
            'experience_years' => $data['experience_years'],
            'hourly_rate' => $data['hourly_rate'],
            'service_area' => $data['service_area'],
            'availability' => $data['availability'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Provider profile updated successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Provider profile updated successfully.');
    }

    public function storeCategory(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(4)),
            'icon' => $data['icon'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Category created successfully.',
            ]);
        }

        return back()->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . $category->id,
            'icon' => $data['icon'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Category updated successfully.',
            ]);
        }

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroyCategory(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);
        $category->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Category deleted successfully.',
            ]);
        }

        return back()->with('success', 'Category deleted successfully.');
    }

    public function editAdminService(Request $request, Service $service)
    {
        $this->ensureAdmin($request);

        return view('admin.service-form', [
            'service' => $service,
            'categories' => Category::orderBy('name')->get(),
            'providers' => Provider::with('user')->orderByDesc('id')->get(),
        ]);
    }

    public function updateAdminService(Request $request, Service $service): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'provider_id' => ['required', 'exists:providers,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'category_ids' => ['nullable', 'array', 'min:1'],
            'category_ids.*' => ['required', 'distinct', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['nullable', Rule::in(['fixed', 'hourly'])],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $categoryIds = collect($data['category_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($categoryIds === [] && isset($data['category_id'])) {
            $categoryIds = [(int) $data['category_id']];
        }

        if ($categoryIds === []) {
            abort(422, 'At least one category is required.');
        }

        $data['short_description'] = trim((string) ($data['short_description'] ?? '')) !== ''
            ? trim((string) $data['short_description'])
            : Str::limit(trim((string) $data['description']), 255, '');
        $data['price'] = (float) ($data['price'] ?? 0);
        $data['price_type'] = trim((string) ($data['price_type'] ?? '')) !== ''
            ? (string) $data['price_type']
            : 'fixed';
        $data['duration_minutes'] = (int) ($data['duration_minutes'] ?? 0);

        $data['category_id'] = $categoryIds[0];
        unset($data['category_ids']);

        $service->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);
        $service->categories()->sync($categoryIds);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Service updated by admin successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Service updated by admin successfully.');
    }

    public function destroyAdminService(Request $request, Service $service): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);
        $service->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Service deleted by admin successfully.',
            ]);
        }

        return back()->with('success', 'Service deleted by admin successfully.');
    }

    public function approveProvider(Provider $provider): RedirectResponse|JsonResponse
    {
        $provider->update([
            'approved_at' => $provider->approved_at ? null : now(),
        ]);

        MarketplaceNotification::create([
            'user_id' => $provider->user_id,
            'title' => $provider->approved_at ? 'Profile approved' : 'Approval pending',
            'message' => $provider->approved_at ? 'Your provider profile is now live for customers.' : 'Your provider profile was moved back to pending review.',
            'type' => $provider->approved_at ? 'success' : 'warning',
            'action_url' => '/dashboard',
            'is_read' => false,
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Provider approval status updated.',
            ]);
        }

        return back()->with('success', 'Provider approval status updated.');
    }

    protected function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }

    protected function adminTrafficSummary(): array
    {
        return [
            'today' => WebsiteVisit::query()->whereDate('visited_at', today())->count(),
            'top_sources' => WebsiteVisit::query()
                ->selectRaw('source, count(*) as total')
                ->groupBy('source')
                ->orderByDesc('total')
                ->take(5)
                ->get(),
            'top_pages' => WebsiteVisit::query()
                ->selectRaw('path, count(*) as total')
                ->groupBy('path')
                ->orderByDesc('total')
                ->take(5)
                ->get(),
            'latest_visits' => WebsiteVisit::query()
                ->latest('visited_at')
                ->take(8)
                ->get(),
        ];
    }

    protected function providerTrafficSummary(?int $providerId): array
    {
        if (! $providerId) {
            return [
                'total_views' => 0,
                'today_views' => 0,
                'top_sources' => collect(),
            ];
        }

        $query = WebsiteVisit::query()->where('provider_id', $providerId);

        return [
            'total_views' => (clone $query)->count(),
            'today_views' => (clone $query)->whereDate('visited_at', today())->count(),
            'top_sources' => (clone $query)
                ->selectRaw('source, count(*) as total')
                ->groupBy('source')
                ->orderByDesc('total')
                ->take(5)
                ->get(),
        ];
    }

    protected function customerTrafficSummary(int $userId): array
    {
        return [
            'your_visits' => WebsiteVisit::query()->where('user_id', $userId)->count(),
            'today_site_visits' => WebsiteVisit::query()->whereDate('visited_at', today())->count(),
            'top_source' => WebsiteVisit::query()
                ->selectRaw('source, count(*) as total')
                ->groupBy('source')
                ->orderByDesc('total')
                ->value('source') ?? 'Direct',
        ];
    }
}
