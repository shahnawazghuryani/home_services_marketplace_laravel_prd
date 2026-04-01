<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Category;
use App\Models\GuideVideo;
use App\Models\MarketplaceNotification;
use App\Models\Provider;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\WebsiteVisit;
use App\Services\ContentSafety;
use App\Services\LogHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ContentSafety $contentSafety,
        private readonly LogHealth $logHealth,
    ) {
    }

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
                'logHealth' => $this->logHealth->summary(),
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
            $providerVisitSummary = $this->providerVisitSummaryMap();
            $providerRecentReviews = Review::query()
                ->with(['customer:id,name'])
                ->latest()
                ->get()
                ->groupBy('provider_id');

            return response()->json([
                'role' => 'admin',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'notifications' => $notifications,
                'support' => config('services.support'),
                'operations' => [
                    'contact_url' => route('contact'),
                    'provider_onboarding_url' => route('provider-onboarding'),
                ],
                'logHealth' => $this->logHealth->summary(),
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
                'providers' => Provider::with('user')->latest()->get()->map(function ($provider) use ($providerVisitSummary, $providerRecentReviews) {
                    $visitSummary = $providerVisitSummary[$provider->id] ?? ['total_views' => 0, 'today_views' => 0];
                    $recentReviews = ($providerRecentReviews->get($provider->user_id) ?? collect())
                        ->take(3)
                        ->map(fn ($review) => [
                            'rating' => (int) $review->rating,
                            'comment' => $review->comment,
                            'customer_name' => $review->customer?->name ?? 'Customer',
                        ])
                        ->values()
                        ->all();

                    return [
                        'id' => $provider->id,
                        'name' => $provider->user->name,
                        'email' => $provider->user->email,
                        'login_username' => $provider->user->email,
                        'login_password_note' => 'Password is encrypted and cannot be displayed.',
                        'service_area' => $provider->service_area,
                        'approved' => (bool) $provider->approved_at,
                        'total_views' => (int) $visitSummary['total_views'],
                        'today_views' => (int) $visitSummary['today_views'],
                        'recent_reviews' => $recentReviews,
                        'approval_url' => route('admin.providers.approve', $provider),
                        'impersonate_url' => route('admin.providers.impersonate', $provider),
                    ];
                }),
                'adminCategories' => Category::latest()->get()->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'description' => $category->description,
                    'update_url' => route('admin.categories.update', $category),
                    'delete_url' => route('admin.categories.destroy', $category),
                ]),
                'guideVideos' => GuideVideo::query()->orderBy('sort_order')->orderBy('id')->get()->map(fn ($guide) => $this->guideVideoPayload($guide)),
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
            $reviews = $profile
                ? $profile->reviews()->with(['customer:id,name'])->latest()->get()
                : collect();

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
                'reviews' => $reviews->map(fn ($review) => [
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'customer_name' => $review->customer?->name ?? 'Customer',
                ])->values(),
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
            'bookings' => $bookings->map(function ($booking) {
                $review = $booking->reviews->first();

                return [
                    'id' => $booking->id,
                    'service' => $booking->service->title,
                    'provider' => $booking->provider->name,
                    'schedule' => optional($booking->scheduled_at)->format('d M Y, h:i A'),
                    'status' => $booking->status,
                    'cancel_url' => route('bookings.status', $booking),
                    'review_url' => route('bookings.reviews.store', $booking),
                    'has_review' => (bool) $review,
                    'can_review' => $booking->status === 'completed',
                    'review' => $review ? [
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                    ] : null,
                ];
            }),
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

        $this->contentSafety->ensureCleanText([
            'bio' => $data['bio'],
            'service_area' => $data['service_area'],
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

        $this->contentSafety->ensureCleanText([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
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

        $this->contentSafety->ensureCleanText([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
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

    public function storeGuide(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $this->validateGuide($request);

        $guide = GuideVideo::create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']) . '-' . Str::lower(Str::random(4)),
            'audience' => $data['audience'] ?? null,
            'summary' => $data['summary'] ?? null,
            'duration' => $data['duration'] ?? null,
            'steps' => $this->normalizeGuideLines($data['steps_text'] ?? ''),
            'voiceover' => $this->normalizeGuideLines($data['voiceover_text'] ?? ''),
            'captions' => $this->normalizeGuideLines($data['captions_text'] ?? ''),
            'video_type' => $data['video_type'],
            'video_url' => $data['video_type'] === 'youtube' ? ($data['video_url'] ?? null) : null,
            'video_path' => $data['video_type'] === 'mp4' ? $this->uploadGuideVideoFile($request) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Guide video created successfully.',
                'guide' => $this->guideVideoPayload($guide),
            ]);
        }

        return back()->with('success', 'Guide video created successfully.');
    }

    public function updateGuide(Request $request, GuideVideo $guideVideo): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $this->validateGuide($request, true);

        $guideVideo->update([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']) . '-' . $guideVideo->id,
            'audience' => $data['audience'] ?? null,
            'summary' => $data['summary'] ?? null,
            'duration' => $data['duration'] ?? null,
            'steps' => $this->normalizeGuideLines($data['steps_text'] ?? ''),
            'voiceover' => $this->normalizeGuideLines($data['voiceover_text'] ?? ''),
            'captions' => $this->normalizeGuideLines($data['captions_text'] ?? ''),
            'video_type' => $data['video_type'],
            'video_url' => $data['video_type'] === 'youtube' ? ($data['video_url'] ?? null) : null,
            'video_path' => $data['video_type'] === 'mp4'
                ? $this->uploadGuideVideoFile($request, $guideVideo->video_path)
                : $this->deleteGuideVideoFileAndReturnNull($guideVideo->video_path),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', false),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Guide video updated successfully.',
                'guide' => $this->guideVideoPayload($guideVideo->fresh()),
            ]);
        }

        return back()->with('success', 'Guide video updated successfully.');
    }

    public function destroyGuide(Request $request, GuideVideo $guideVideo): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);
        $this->deleteGuideVideoFile($guideVideo->video_path);
        $guideVideo->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Guide video deleted successfully.',
            ]);
        }

        return back()->with('success', 'Guide video deleted successfully.');
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

        $this->contentSafety->ensureCleanText([
            'title' => $data['title'],
            'short_description' => $data['short_description'],
            'description' => $data['description'],
        ]);

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
        $this->setProviderApprovalState($provider, ! $provider->approved_at);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Provider approval status updated.',
            ]);
        }

        return back()->with('success', 'Provider approval status updated.');
    }

    public function impersonateProvider(Request $request, Provider $provider): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        Auth::loginUsingId($provider->user_id);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged in as provider successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Logged in as provider successfully.');
    }

    public function providerApprovalLink(Request $request, Provider $provider, string $action): Response
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless(in_array($action, ['approve', 'deactivate'], true), 404);

        $approved = $action === 'approve';
        $this->setProviderApprovalState($provider, $approved);
        $provider = $provider->fresh('user');
        $statusLabel = $approved ? 'Provider activated' : 'Provider deactivated';
        $currentStatus = $approved ? 'Active' : 'Inactive / Pending';

        $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Provider Approval Updated</title>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #0b0c0f; color: #f5f7fa; }
        .wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { width: min(680px, 100%); background: #17191c; border: 1px solid rgba(255,255,255,.08); border-radius: 22px; padding: 28px; box-shadow: 0 24px 60px rgba(0,0,0,.28); }
        .pill { display: inline-block; padding: 8px 12px; border-radius: 999px; background: rgba(244,180,0,.14); color: #ffd45c; font-weight: 700; margin-bottom: 16px; }
        .row { margin: 10px 0; color: #c2c8d0; }
        .row strong { color: #fff; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="pill">{$statusLabel}</div>
            <h1 style="margin:0 0 12px;">{$provider->user->name}</h1>
            <p style="margin:0 0 18px;color:#c2c8d0;">Provider status has been updated successfully using the secure email link.</p>
            <div class="row"><strong>Email:</strong> {$provider->user->email}</div>
            <div class="row"><strong>Phone:</strong> {$provider->user->phone}</div>
            <div class="row"><strong>City:</strong> {$provider->user->city}</div>
            <div class="row"><strong>Service area:</strong> {$provider->service_area}</div>
            <div class="row"><strong>Current status:</strong> {$currentStatus}</div>
        </div>
    </div>
</body>
</html>
HTML;

        return response($html);
    }

    protected function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }

    protected function setProviderApprovalState(Provider $provider, bool $approved): void
    {
        $provider->update([
            'approved_at' => $approved ? now() : null,
        ]);

        MarketplaceNotification::create([
            'user_id' => $provider->user_id,
            'title' => $approved ? 'Profile approved' : 'Approval pending',
            'message' => $approved ? 'Your provider profile is now live for customers.' : 'Your provider profile was moved back to pending review.',
            'type' => $approved ? 'success' : 'warning',
            'action_url' => '/dashboard',
            'is_read' => false,
        ]);
    }

    protected function validateGuide(Request $request, bool $isUpdate = false): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'audience' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:1200'],
            'duration' => ['nullable', 'string', 'max:20'],
            'steps_text' => ['nullable', 'string', 'max:5000'],
            'voiceover_text' => ['nullable', 'string', 'max:8000'],
            'captions_text' => ['nullable', 'string', 'max:5000'],
            'video_type' => ['required', Rule::in(['youtube', 'mp4'])],
            'video_url' => ['nullable', 'string', 'max:1000'],
            'video_file' => [$isUpdate ? 'nullable' : 'nullable', 'file', 'mimetypes:video/mp4,video/quicktime', 'max:51200'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->contentSafety->ensureCleanText([
            'title' => $data['title'],
            'summary' => $data['summary'] ?? '',
            'steps' => $data['steps_text'] ?? '',
            'voiceover' => $data['voiceover_text'] ?? '',
            'captions' => $data['captions_text'] ?? '',
        ]);

        if (($data['video_type'] ?? '') === 'youtube' && trim((string) ($data['video_url'] ?? '')) === '') {
            abort(422, 'YouTube link required.');
        }

        if (($data['video_type'] ?? '') === 'mp4' && ! $request->hasFile('video_file') && ! $isUpdate) {
            abort(422, 'MP4 file required.');
        }

        return $data;
    }

    protected function normalizeGuideLines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', trim($value)) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }

    protected function uploadGuideVideoFile(Request $request, ?string $existingPath = null): ?string
    {
        if (! $request->hasFile('video_file')) {
            return $existingPath;
        }

        $directory = public_path('guide-videos');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $this->deleteGuideVideoFile($existingPath);

        $file = $request->file('video_file');
        $filename = now()->format('YmdHis') . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'guide-videos/' . $filename;
    }

    protected function deleteGuideVideoFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        $fullPath = public_path($path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    protected function deleteGuideVideoFileAndReturnNull(?string $path): ?string
    {
        $this->deleteGuideVideoFile($path);

        return null;
    }

    protected function guideVideoPayload(GuideVideo $guide): array
    {
        return [
            'id' => $guide->id,
            'title' => $guide->title,
            'audience' => $guide->audience,
            'summary' => $guide->summary,
            'duration' => $guide->duration,
            'steps' => $guide->steps ?? [],
            'voiceover' => $guide->voiceover ?? [],
            'captions' => $guide->captions ?? [],
            'steps_text' => implode("\n", $guide->steps ?? []),
            'voiceover_text' => implode("\n", $guide->voiceover ?? []),
            'captions_text' => implode("\n", $guide->captions ?? []),
            'video_type' => $guide->video_type,
            'video_url' => $guide->video_url,
            'video_path' => $guide->video_path ? asset($guide->video_path) : null,
            'sort_order' => (int) $guide->sort_order,
            'is_active' => (bool) $guide->is_active,
            'update_url' => route('admin.guides.update', $guide),
            'delete_url' => route('admin.guides.destroy', $guide),
        ];
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

    protected function providerVisitSummaryMap(): array
    {
        return WebsiteVisit::query()
            ->selectRaw(
                'provider_id, COUNT(*) as total_views, SUM(CASE WHEN DATE(visited_at) = ? THEN 1 ELSE 0 END) as today_views',
                [today()->toDateString()]
            )
            ->whereNotNull('provider_id')
            ->groupBy('provider_id')
            ->get()
            ->mapWithKeys(fn ($item) => [
                (int) $item->provider_id => [
                    'total_views' => (int) $item->total_views,
                    'today_views' => (int) $item->today_views,
                ],
            ])
            ->all();
    }
}
