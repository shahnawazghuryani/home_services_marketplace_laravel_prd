<?php

namespace App\Services\AI;

use Illuminate\Support\Str;
use RuntimeException;

class MarketplaceAiService
{
    public function __construct(
        private readonly OpenAIResponsesClient $client,
    ) {
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
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<string, mixed>
     */
    public function providerServiceBuilder(array $payload, array $categories): array
    {
        if ($this->featureEnabled('provider_service_builder') && $this->apiConfigured()) {
            $prompt = <<<TEXT
You help a home service provider create a clean marketplace listing draft.
Return JSON only with this shape:
{
  "title": "service title",
  "short_description": "short pitch under 255 chars",
  "description": "clear provider-facing service description",
  "price": 2500,
  "price_type": "fixed",
  "duration_minutes": 60,
  "suggested_category_ids": [1, 2],
  "image_prompt": "short visual direction",
  "tags": ["tag one", "tag two"]
}

Provider input:
%s

Available categories:
%s
TEXT;

            try {
                $response = $this->client->json([
                    $this->systemMessage('You turn rough provider notes into a launch-ready home service listing draft.'),
                    $this->userMessage(sprintf(
                        $prompt,
                        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    )),
                ], (float) config('ai.features.provider_service_builder.temperature'));

                return $this->finalizeServiceBuilderDraft($response, $payload, $categories);
            } catch (RuntimeException $exception) {
                if (! $this->fallbackEnabled()) {
                    throw $exception;
                }
            }
        }

        return $this->fallbackProviderServiceBuilder($payload, $categories);
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

    private function featureEnabled(string $feature): bool
    {
        return (bool) config("ai.features.{$feature}.enabled", false);
    }

    private function apiConfigured(): bool
    {
        return trim((string) config('services.openai.api_key', '')) !== '';
    }

    private function fallbackEnabled(): bool
    {
        return (bool) config('ai.fallback_enabled', false);
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
                    $serviceCategorySlugs = array_map(
                        fn ($slug) => strtolower((string) $slug),
                        (array) ($service['category_slugs'] ?? [])
                    );

                    if (
                        ($serviceCategory !== '' && $serviceCategory === $category)
                        || in_array($category, $serviceCategorySlugs, true)
                    ) {
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
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<string, mixed>
     */
    private function fallbackProviderServiceBuilder(array $payload, array $categories): array
    {
        $rawPrompt = trim((string) ($payload['prompt'] ?? ''));
        $normalized = Str::of($rawPrompt)->lower()->squish()->value();

        $keywordMap = [
            'plumbing' => ['plumber', 'plumbing', 'pipe', 'leak', 'bathroom', 'tap', 'washroom', 'sanitary'],
            'electrician' => ['electrician', 'electrical', 'wiring', 'switch', 'socket', 'power', 'fan', 'light'],
            'ac-repair' => ['ac', 'air conditioner', 'cooling', 'split ac', 'gas refill', 'compressor'],
            'deep-cleaning' => ['clean', 'cleaning', 'deep cleaning', 'sofa', 'carpet', 'washroom'],
            'carpentry' => ['carpenter', 'carpentry', 'wood', 'door', 'cabinet', 'furniture'],
            'appliance-repair' => ['appliance', 'washing machine', 'fridge', 'refrigerator', 'microwave'],
        ];

        $matchedCategoryIds = [];
        foreach ($categories as $category) {
            $slug = Str::slug((string) ($category['name'] ?? ''));
            $name = Str::lower((string) ($category['name'] ?? ''));
            $needles = $keywordMap[$slug] ?? [$slug, $name];

            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($normalized, Str::lower($needle))) {
                    $matchedCategoryIds[] = (int) $category['id'];
                    break;
                }
            }
        }

        if ($matchedCategoryIds === []) {
            $matchedCategoryIds[] = (int) ($categories[0]['id'] ?? 0);
        }

        $matchedCategoryIds = array_values(array_filter(array_unique($matchedCategoryIds)));
        $matchedCategories = array_values(array_filter($categories, fn ($category) => in_array((int) $category['id'], $matchedCategoryIds, true)));
        $categoryNames = array_map(fn ($category) => (string) $category['name'], $matchedCategories);
        $primaryCategory = $categoryNames[0] ?? 'Home Service';

        $title = $this->serviceTitleFromPrompt($rawPrompt, $categoryNames);
        $shortDescription = $this->serviceShortDescription($rawPrompt, $categoryNames);
        $description = $this->serviceDescription($rawPrompt, $categoryNames);
        $price = $this->suggestedPrice($normalized, $categoryNames);
        $duration = $this->suggestedDuration($normalized, $categoryNames);
        $imagePrompt = 'Soft clean marketplace card for ' . $primaryCategory . ' service';

        return $this->finalizeServiceBuilderDraft([
            'title' => $title,
            'short_description' => $shortDescription,
            'description' => $description,
            'price' => $price,
            'price_type' => 'fixed',
            'duration_minutes' => $duration,
            'suggested_category_ids' => $matchedCategoryIds,
            'image_prompt' => $imagePrompt,
            'tags' => $categoryNames,
        ], $payload, $categories);
    }

    /**
     * @param  array<string, mixed>  $draft
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<string, mixed>
     */
    private function finalizeServiceBuilderDraft(array $draft, array $payload, array $categories): array
    {
        $suggestedCategoryIds = collect((array) ($draft['suggested_category_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($suggestedCategoryIds === [] && isset($categories[0]['id'])) {
            $suggestedCategoryIds = [(int) $categories[0]['id']];
        }

        $title = trim((string) ($draft['title'] ?? 'Professional Home Service'));
        $shortDescription = Str::limit(trim((string) ($draft['short_description'] ?? $title)), 255, '');
        $description = trim((string) ($draft['description'] ?? $shortDescription));
        $price = max(0, (int) round((float) ($draft['price'] ?? 0)));
        $duration = max(30, (int) ($draft['duration_minutes'] ?? 60));
        $priceType = in_array(($draft['price_type'] ?? 'fixed'), ['fixed', 'hourly'], true)
            ? (string) $draft['price_type']
            : 'fixed';
        $imagePrompt = trim((string) ($draft['image_prompt'] ?? 'Soft home service illustration'));
        $svg = $this->serviceImageSvg($title, $imagePrompt);

        return [
            'title' => $title,
            'short_description' => $shortDescription,
            'description' => $description,
            'price' => $price,
            'price_type' => $priceType,
            'duration_minutes' => $duration,
            'suggested_category_ids' => $suggestedCategoryIds,
            'image_prompt' => $imagePrompt,
            'tags' => array_values(array_map('strval', (array) ($draft['tags'] ?? []))),
            'generated_image_svg' => $svg,
            'image_preview_url' => 'data:image/svg+xml;base64,' . base64_encode($svg),
            'source_prompt' => trim((string) ($payload['prompt'] ?? '')),
        ];
    }

    /**
     * @param  array<int, string>  $categoryNames
     */
    private function serviceTitleFromPrompt(string $prompt, array $categoryNames): string
    {
        $normalized = Str::of($prompt)->trim()->squish()->value();

        if ($normalized !== '' && str_word_count($normalized) >= 2 && mb_strlen($normalized) <= 45) {
            return Str::title($normalized);
        }

        if (count($categoryNames) >= 2) {
            return implode(' & ', array_slice($categoryNames, 0, 2)) . ' Home Visit';
        }

        return ($categoryNames[0] ?? 'Professional Home Service') . ' Service';
    }

    /**
     * @param  array<int, string>  $categoryNames
     */
    private function serviceShortDescription(string $prompt, array $categoryNames): string
    {
        $focus = trim($prompt) !== '' ? trim($prompt) : implode(', ', $categoryNames);
        return Str::limit('Quick professional help for ' . Str::lower($focus) . ' with neat work, clear pricing, and home visit support.', 255, '');
    }

    /**
     * @param  array<int, string>  $categoryNames
     */
    private function serviceDescription(string $prompt, array $categoryNames): string
    {
        $categoryLine = implode(', ', $categoryNames);
        $focus = trim($prompt) !== '' ? trim($prompt) : $categoryLine;

        return 'This service is ideal for customers who need reliable ' . Str::lower($focus)
            . '. I handle inspection, basic diagnosis, neat finishing, and practical on-site support. '
            . 'If you share photos or issue details before arrival, I can come better prepared and complete the work faster.';
    }

    /**
     * @param  array<int, string>  $categoryNames
     */
    private function suggestedPrice(string $normalizedPrompt, array $categoryNames): int
    {
        $categoryText = Str::lower(implode(' ', $categoryNames));

        if (str_contains($normalizedPrompt, 'elect') || str_contains($categoryText, 'electric')) {
            return 2800;
        }

        if (str_contains($normalizedPrompt, 'plumb') || str_contains($categoryText, 'plumb')) {
            return 2500;
        }

        if (str_contains($normalizedPrompt, 'ac') || str_contains($categoryText, 'ac')) {
            return 3500;
        }

        if (str_contains($normalizedPrompt, 'clean') || str_contains($categoryText, 'clean')) {
            return 3000;
        }

        return 2500;
    }

    /**
     * @param  array<int, string>  $categoryNames
     */
    private function suggestedDuration(string $normalizedPrompt, array $categoryNames): int
    {
        $categoryText = Str::lower(implode(' ', $categoryNames));

        if (str_contains($normalizedPrompt, 'install') || str_contains($normalizedPrompt, 'repair')) {
            return 90;
        }

        if (str_contains($categoryText, 'clean')) {
            return 120;
        }

        return 60;
    }

    private function serviceImageSvg(string $title, string $imagePrompt): string
    {
        $safeTitle = e(Str::limit($title, 28, ''));
        $safePrompt = e(Str::limit($imagePrompt, 50, ''));

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800" role="img" aria-label="{$safeTitle}">
  <defs>
    <linearGradient id="bg" x1="0%" x2="100%" y1="0%" y2="100%">
      <stop offset="0%" stop-color="#f5efe3"/>
      <stop offset="50%" stop-color="#dfe8f6"/>
      <stop offset="100%" stop-color="#d7f0e5"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/>
  <circle cx="950" cy="140" r="120" fill="#ffffff" fill-opacity="0.45"/>
  <circle cx="220" cy="650" r="180" fill="#ffffff" fill-opacity="0.28"/>
  <rect x="110" y="110" width="980" height="580" rx="42" fill="#ffffff" fill-opacity="0.72"/>
  <rect x="160" y="170" width="170" height="170" rx="34" fill="#183153" fill-opacity="0.92"/>
  <path d="M220 210c-30 0-54 24-54 54s24 54 54 54 54-24 54-54-24-54-54-54zm0 20c19 0 34 15 34 34s-15 34-34 34-34-15-34-34 15-34 34-34z" fill="#f5efe3"/>
  <path d="M202 286l-30 55h22l18-32 18 32h22l-30-55z" fill="#f5efe3"/>
  <text x="390" y="255" font-family="Segoe UI, Arial, sans-serif" font-size="60" font-weight="700" fill="#183153">{$safeTitle}</text>
  <text x="390" y="320" font-family="Segoe UI, Arial, sans-serif" font-size="28" fill="#355070">{$safePrompt}</text>
  <rect x="390" y="390" width="300" height="20" rx="10" fill="#8fb8a8"/>
  <rect x="390" y="430" width="420" height="20" rx="10" fill="#b7c9e2"/>
  <rect x="390" y="470" width="370" height="20" rx="10" fill="#e0b97a"/>
  <rect x="390" y="540" width="190" height="64" rx="18" fill="#183153"/>
  <text x="485" y="582" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="30" font-weight="700" fill="#ffffff">Ready</text>
</svg>
SVG;
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

}
