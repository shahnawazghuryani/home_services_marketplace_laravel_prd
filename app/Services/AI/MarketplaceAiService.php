<?php

namespace App\Services\AI;

use App\Models\Category;
use RuntimeException;

class MarketplaceAiService
{
    public function __construct(
        private readonly OpenAIResponsesClient $client,
    ) {
    }

    public function smartSearch(string $problem, ?string $location = null): array
    {
        $this->ensureFeatureEnabled('smart_search');

        $categories = Category::query()
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->map(fn (Category $category) => [
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->values()
            ->all();

        $prompt = <<<TEXT
You are helping users search home services in Pakistan.
Return JSON only with this shape:
{
  "search_query": "short search phrase",
  "category_slug": "best matched category slug or null",
  "urgency": "low|medium|high",
  "summary": "short plain-language summary",
  "filters": {
    "location": "location or null"
  },
  "follow_up_questions": ["question 1", "question 2"]
}

Available categories:
%s

User problem: %s
User location: %s
TEXT;

        try {
            return $this->client->json([
                $this->systemMessage('You convert messy user problem statements into structured marketplace search help.'),
                $this->userMessage(sprintf(
                    $prompt,
                    json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $problem,
                    $location ?: 'not provided'
                )),
            ], (float) config('ai.features.smart_search.temperature'));
        } catch (RuntimeException $exception) {
            if (! $this->fallbackEnabled()) {
                throw $exception;
            }

            return $this->fallbackSmartSearch($problem, $location, $categories);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function bookingAssistant(array $payload): array
    {
        $this->ensureFeatureEnabled('booking_helper');

        $prompt = <<<TEXT
You are helping a customer complete a home service booking.
Return JSON only with this shape:
{
  "customer_summary": "clean short summary for provider",
  "missing_fields": ["field_name"],
  "booking_tip": "one useful suggestion",
  "risk_flags": ["flag text"]
}

Booking context:
%s
TEXT;

        try {
            return $this->client->json([
                $this->systemMessage('You turn raw booking details into clear provider-friendly summaries.'),
                $this->userMessage(sprintf(
                    $prompt,
                    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                )),
            ], (float) config('ai.features.booking_helper.temperature'));
        } catch (RuntimeException $exception) {
            if (! $this->fallbackEnabled()) {
                throw $exception;
            }

            return $this->fallbackBookingAssistant($payload);
        }
    }

    /**
     * @param  array<string, mixed>  $requestContext
     * @param  array<int, array<string, mixed>>  $providers
     */
    public function providerRecommendations(array $requestContext, array $providers): array
    {
        $this->ensureFeatureEnabled('provider_recommendations');

        $prompt = <<<TEXT
You are ranking home service providers for a user.
Return JSON only with this shape:
{
  "summary": "short recommendation summary",
  "recommended_provider_ids": [1, 2, 3],
  "reasons": [
    {
      "provider_id": 1,
      "reason": "short human-friendly reason"
    }
  ]
}

User request:
%s

Provider candidates:
%s
TEXT;

        try {
            return $this->client->json([
                $this->systemMessage('You rank providers using user need, area fit, and service relevance.'),
                $this->userMessage(sprintf(
                    $prompt,
                    json_encode($requestContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    json_encode($providers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                )),
            ], (float) config('ai.features.provider_recommendations.temperature'));
        } catch (RuntimeException $exception) {
            if (! $this->fallbackEnabled()) {
                throw $exception;
            }

            return $this->fallbackProviderRecommendations($requestContext, $providers);
        }
    }

    /**
     * @return array<string, string>
     */
    private function systemMessage(string $text): array
    {
        return [
            'role' => 'system',
            'content' => $text,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function userMessage(string $text): array
    {
        return [
            'role' => 'user',
            'content' => $text,
        ];
    }

    private function ensureFeatureEnabled(string $feature): void
    {
        if (! config("ai.features.{$feature}.enabled")) {
            throw new RuntimeException("The AI feature [{$feature}] is disabled.");
        }
    }

    private function fallbackEnabled(): bool
    {
        return (bool) config('ai.fallback_enabled', false);
    }

    /**
     * @param  array<int, array{name: string, slug: string}>  $categories
     * @return array<string, mixed>
     */
    private function fallbackSmartSearch(string $problem, ?string $location, array $categories): array
    {
        $problemLower = strtolower($problem);
        $words = preg_split('/[^a-z0-9]+/i', $problemLower) ?: [];
        $words = array_values(array_filter($words, fn ($word) => strlen((string) $word) >= 3));

        $categoryHints = [
            'plumbing' => ['plumber', 'plumbing', 'pipe', 'tap', 'leak', 'bathroom', 'drain', 'sewer', 'naali', 'nalka'],
            'electric' => ['electric', 'electrician', 'wiring', 'switch', 'socket', 'short', 'spark', 'fan', 'light', 'bulb'],
            'ac' => ['ac', 'aircondition', 'cooling', 'compressor', 'indoor', 'outdoor'],
            'cleaning' => ['clean', 'cleaning', 'sofa', 'carpet', 'deep clean', 'wash'],
            'painting' => ['paint', 'painter', 'wall', 'color', 'polish'],
            'carpentry' => ['carpenter', 'wood', 'door', 'cabinet', 'furniture'],
        ];

        $bestSlug = $this->pickCategoryByHints($problemLower, $categories, $categoryHints);
        foreach ($categories as $category) {
            if ($bestSlug !== null) {
                break;
            }

            $name = strtolower((string) ($category['name'] ?? ''));
            $slug = strtolower((string) ($category['slug'] ?? ''));

            foreach ($words as $word) {
                if ($word !== '' && (str_contains($name, $word) || str_contains($slug, $word) || str_contains($problemLower, $slug))) {
                    $bestSlug = $category['slug'];
                    break 2;
                }
            }
        }

        $urgency = 'medium';
        if ($this->containsAny($problemLower, ['urgent', 'emergency', 'jaldi', 'immediately', 'leak', 'spark', 'smoke', 'gas'])) {
            $urgency = 'high';
        } elseif ($this->containsAny($problemLower, ['routine', 'later', 'weekend', 'maintenance'])) {
            $urgency = 'low';
        }

        $searchQuery = $this->buildSearchQuery($problem, $bestSlug);
        $followUps = $this->buildFollowUpQuestions($bestSlug);

        return [
            'search_query' => $searchQuery,
            'category_slug' => $bestSlug,
            'urgency' => $urgency,
            'summary' => 'Smart local helper ne aapki request ko structured search me convert kar diya hai.',
            'filters' => [
                'location' => $location ?: null,
            ],
            'follow_up_questions' => $followUps,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function fallbackBookingAssistant(array $payload): array
    {
        $missing = [];
        foreach (['location', 'preferred_time', 'budget'] as $field) {
            if (! isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                $missing[] = $field;
            }
        }

        $problem = strtolower((string) ($payload['problem'] ?? ''));
        $riskFlags = [];
        if ($this->containsAny($problem, ['spark', 'smoke', 'burn', 'gas', 'short'])) {
            $riskFlags[] = 'Potential safety risk - provider ko visit se pehle warn karein.';
        }
        if ($this->containsAny($problem, ['leak', 'water', 'flood'])) {
            $riskFlags[] = 'Water damage risk - jaldi inspection recommend hai.';
        }

        return [
            'customer_summary' => sprintf(
                '%s issue reported: %s. Location: %s. Preferred time: %s.',
                (string) ($payload['service_title'] ?? 'Service'),
                (string) ($payload['problem'] ?? 'Issue details not provided'),
                (string) ($payload['location'] ?? 'Not provided'),
                (string) ($payload['preferred_time'] ?? 'Not provided')
            ),
            'missing_fields' => $missing,
            'booking_tip' => 'Provider ko clear photos aur exact address share karein taake first-visit fix chance barhe.',
            'risk_flags' => $riskFlags,
        ];
    }

    /**
     * @param  array<string, mixed>  $requestContext
     * @param  array<int, array<string, mixed>>  $providers
     * @return array<string, mixed>
     */
    private function fallbackProviderRecommendations(array $requestContext, array $providers): array
    {
        $location = strtolower((string) ($requestContext['location'] ?? ''));
        $category = strtolower((string) ($requestContext['category_slug'] ?? ''));

        $scored = array_map(function (array $provider) use ($location, $category) {
            $score = 0;

            $serviceArea = strtolower((string) ($provider['service_area'] ?? ''));
            $city = strtolower((string) ($provider['city'] ?? ''));
            if ($location !== '' && (str_contains($serviceArea, $location) || str_contains($city, $location))) {
                $score += 3;
            }

            $experience = (int) ($provider['experience_years'] ?? 0);
            $score += min(3, (int) floor($experience / 3));

            $hourlyRate = (float) ($provider['hourly_rate'] ?? 0);
            if ($hourlyRate > 0 && $hourlyRate <= 2500) {
                $score += 2;
            }

            if ($category !== '') {
                foreach (($provider['services'] ?? []) as $service) {
                    $serviceCategory = strtolower((string) ($service['category_slug'] ?? ''));
                    if ($serviceCategory !== '' && $serviceCategory === $category) {
                        $score += 4;
                        break;
                    }
                }
            }

            $provider['__score'] = $score;

            return $provider;
        }, $providers);

        usort($scored, fn ($a, $b) => ($b['__score'] ?? 0) <=> ($a['__score'] ?? 0));
        $top = array_slice($scored, 0, 3);

        return [
            'summary' => 'Live AI unavailable thi, is liye local ranking logic se best providers suggest kiye gaye.',
            'recommended_provider_ids' => array_values(array_map(fn ($provider) => (int) $provider['id'], $top)),
            'reasons' => array_values(array_map(function ($provider) {
                return [
                    'provider_id' => (int) $provider['id'],
                    'reason' => 'Area fit, experience, aur pricing ke basis par recommend kiya gaya.',
                ];
            }, $top)),
        ];
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array{name: string, slug: string}> $categories
     * @param array<string, array<int, string>> $categoryHints
     */
    private function pickCategoryByHints(string $problemLower, array $categories, array $categoryHints): ?string
    {
        foreach ($categories as $category) {
            $slug = strtolower((string) ($category['slug'] ?? ''));
            $name = strtolower((string) ($category['name'] ?? ''));

            foreach ($categoryHints as $hintGroup => $keywords) {
                if (! str_contains($slug, $hintGroup) && ! str_contains($name, $hintGroup)) {
                    continue;
                }

                if ($this->containsAny($problemLower, $keywords)) {
                    return $category['slug'];
                }
            }
        }

        return null;
    }

    private function buildSearchQuery(string $problem, ?string $bestSlug): string
    {
        if ($bestSlug !== null) {
            $prefix = str_replace(['-', '_'], ' ', trim($bestSlug));

            return trim($prefix . ' service near me');
        }

        $clean = trim(preg_replace('/\s+/', ' ', $problem) ?: '');
        if ($clean === '') {
            return 'home service near me';
        }

        return mb_substr($clean, 0, 80);
    }

    /**
     * @return array<int, string>
     */
    private function buildFollowUpQuestions(?string $bestSlug): array
    {
        $slug = strtolower((string) $bestSlug);

        if (str_contains($slug, 'plumb')) {
            return [
                'Leak kis jagah se hai (kitchen, bathroom, ya main line)?',
                'Pani band karna possible hai ya emergency visit chahiye?',
            ];
        }

        if (str_contains($slug, 'elect')) {
            return [
                'Issue ek point par hai ya poore ghar me?',
                'Spark/smell aa rahi ho to breaker off karke details share karein?',
            ];
        }

        if (str_contains($slug, 'ac')) {
            return [
                'AC model aur last service kab hui thi?',
                'Cooling issue ke sath noise ya water leakage bhi hai?',
            ];
        }

        return [
            'Issue kab se aa raha hai aur kitni dafa hota hai?',
            'Koi photo/video share kar sakte hain taake provider better estimate de sake?',
        ];
    }
}
