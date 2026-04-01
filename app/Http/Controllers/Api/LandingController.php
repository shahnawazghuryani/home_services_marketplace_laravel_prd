<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\GuideVideo;
use App\Models\Provider;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->string('search'));
        $requestedCategory = trim((string) $request->string('category'));
        $searchContext = $this->buildSearchContext($search);
        $effectiveCategory = $requestedCategory !== '' ? $requestedCategory : ($searchContext['inferred_category'] ?? '');
        $searchTokens = $searchContext['tokens'] ?? [];

        $servicesQuery = Service::with(['category', 'categories', 'provider.user'])
            ->where('is_active', true)
            ->whereHas('provider', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'));

        if ($effectiveCategory !== '') {
            $servicesQuery->whereHas('categories', fn ($query) => $query->where('slug', $effectiveCategory));
        }

        if ($searchTokens !== []) {
            $servicesQuery->where(fn ($query) => $this->applyServiceSearchTokens($query, $searchTokens));
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
            ->whereNotNull('approved_at')
            ->whereHas('services', fn ($serviceQuery) => $serviceQuery->where('is_active', true));

        if ($effectiveCategory !== '') {
            $providersQuery->whereHas('services', fn ($serviceQuery) => $serviceQuery
                ->where('is_active', true)
                ->whereHas('categories', fn ($categoryQuery) => $categoryQuery->where('slug', $effectiveCategory)));
        }

        if ($searchTokens !== []) {
            $providersQuery->where(fn ($query) => $this->applyProviderSearchTokens($query, $searchTokens));
        }

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

        $providerRatings = Review::query()
            ->selectRaw('provider_id, ROUND(AVG(rating), 1) as rating_avg, COUNT(*) as reviews_count')
            ->groupBy('provider_id')
            ->get()
            ->keyBy('provider_id');

        $services = $servicesQuery->latest()->take(9)->get()->map(function ($service) use ($providerRatings) {
            return [
                'id' => $service->id,
                'title' => $service->title,
                'slug' => $service->slug,
                'short_description' => $service->short_description,
                'price' => (float) $service->price,
                'duration_minutes' => $service->duration_minutes,
                'image_url' => $service->image_path ? asset($service->image_path) : null,
                'category' => $service->category?->name,
                'categories' => $service->categories->pluck('name')->values()->all(),
                'provider' => [
                    'id' => $service->provider->id,
                    'name' => $service->provider->user->name,
                    'phone' => $service->provider->user->phone,
                    'city' => $service->provider->user->city,
                    'service_area' => $service->provider->service_area,
                    'rating_avg' => (float) ($providerRatings->get($service->provider->user_id)->rating_avg ?? 0),
                    'reviews_count' => (int) ($providerRatings->get($service->provider->user_id)->reviews_count ?? 0),
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

        $guides = GuideVideo::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (GuideVideo $guide) => [
                'id' => $guide->id,
                'title' => $guide->title,
                'duration' => $guide->duration,
                'summary' => $guide->summary,
                'audience' => $guide->audience,
                'steps' => $guide->steps ?? [],
                'voiceover' => $guide->voiceover ?? [],
                'captions' => $guide->captions ?? [],
                'videoType' => $guide->video_type,
                'videoUrl' => $guide->video_type === 'mp4' ? ($guide->video_path ? asset($guide->video_path) : null) : $guide->video_url,
                'videoEmbedUrl' => $guide->video_type === 'youtube' ? $this->youtubeEmbedUrl($guide->video_url) : null,
            ])
            ->values();

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
            'guides' => $guides,
            'filters' => array_merge(
                $request->only(['search', 'location', 'category']),
                ['resolved_category' => $effectiveCategory !== '' ? $effectiveCategory : null]
            ),
            'location_suggestions' => $this->locationSuggestions(),
        ]);
    }

    /**
     * @return array{tokens: array<int, string>, inferred_category: string|null}
     */
    protected function buildSearchContext(string $search): array
    {
        $normalized = strtolower(trim($search));
        if ($normalized === '') {
            return ['tokens' => [], 'inferred_category' => null];
        }

        $rawTokens = preg_split('/[^a-z0-9]+/i', $normalized) ?: [];
        $stopWords = [
            'mujhe', 'mje', 'mjy', 'mujhy', 'chahiye', 'chahye', 'chaiye', 'chye', 'do', 'de', 'dijiye', 'please',
            'plz', 'banda', 'banda?', 'number', 'num', 'contact', 'chahie', 'hai', 'ka', 'ki', 'ke', 'mein',
            'me', 'or', 'aur', 'for', 'the', 'a', 'an', 'to', 'krdo', 'kardo', 'karna', 'bnda', 'bande',
        ];

        $tokens = array_values(array_filter($rawTokens, function (string $token) use ($stopWords) {
            return strlen($token) >= 3 && ! in_array($token, $stopWords, true);
        }));

        $categoryHints = [
            'plumbing' => ['plumb', 'plumber', 'leak', 'pipe', 'tap', 'naali', 'nalka', 'drain'],
            'electrical' => ['elect', 'electric', 'bijli', 'wiring', 'switch', 'socket', 'short', 'fan', 'bulb'],
            'ac-repair' => ['ac', 'cooling', 'compressor', 'aircond', 'aircondition'],
            'cleaning' => ['clean', 'safai', 'sofa', 'carpet', 'wash'],
            'painting' => ['paint', 'painter', 'rang'],
            'carpentry' => ['carpenter', 'wood', 'darwaza', 'furniture'],
        ];

        $inferredCategory = null;
        foreach ($categoryHints as $slug => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    $inferredCategory = $slug;
                    break 2;
                }
            }
        }

        return ['tokens' => $tokens, 'inferred_category' => $inferredCategory];
    }

    /**
     * @param array<int, string> $tokens
     */
    protected function applyServiceSearchTokens($query, array $tokens): void
    {
        foreach ($tokens as $index => $token) {
            $method = $index === 0 ? 'where' : 'orWhere';
            $query->{$method}(function ($innerQuery) use ($token) {
                $innerQuery->where('title', 'like', "%{$token}%")
                    ->orWhere('short_description', 'like', "%{$token}%")
                    ->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery
                        ->where('name', 'like', "%{$token}%")
                        ->orWhere('slug', 'like', "%{$token}%"))
                    ->orWhereHas('provider.user', fn ($providerQuery) => $providerQuery
                        ->where('name', 'like', "%{$token}%"));
            });
        }
    }

    /**
     * @param array<int, string> $tokens
     */
    protected function applyProviderSearchTokens($query, array $tokens): void
    {
        foreach ($tokens as $index => $token) {
            $method = $index === 0 ? 'where' : 'orWhere';
            $query->{$method}(function ($innerQuery) use ($token) {
                $innerQuery->where('service_area', 'like', "%{$token}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', "%{$token}%")
                        ->orWhere('city', 'like', "%{$token}%")
                        ->orWhere('address', 'like', "%{$token}%"))
                    ->orWhereHas('services', fn ($serviceQuery) => $serviceQuery
                        ->where('is_active', true)
                        ->where(function ($innerServiceQuery) use ($token) {
                            $innerServiceQuery->where('title', 'like', "%{$token}%")
                                ->orWhere('short_description', 'like', "%{$token}%")
                                ->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery
                                    ->where('name', 'like', "%{$token}%")
                                    ->orWhere('slug', 'like', "%{$token}%"));
                        }));
            });
        }
    }

    protected function locationSuggestions(): array
    {
        return collect()
            ->merge(Provider::query()->whereNotNull('approved_at')->whereNotNull('service_area')->pluck('service_area'))
            ->merge(User::query()
                ->whereHas('providerProfile', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'))
                ->whereNotNull('city')
                ->pluck('city'))
            ->merge(User::query()
                ->whereHas('providerProfile', fn ($providerQuery) => $providerQuery->whereNotNull('approved_at'))
                ->whereNotNull('address')
                ->pluck('address'))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->sort()
            ->take(20)
            ->values()
            ->all();
    }

    protected function youtubeEmbedUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([^&?/]+)~', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        return $url;
    }
}
