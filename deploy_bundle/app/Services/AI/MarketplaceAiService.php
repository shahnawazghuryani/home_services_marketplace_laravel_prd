<?php

namespace App\Services\AI;

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
