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

        return $this->client->json([
            $this->systemMessage('You convert messy user problem statements into structured marketplace search help.'),
            $this->userMessage(sprintf(
                $prompt,
                json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $problem,
                $location ?: 'not provided'
            )),
        ], (float) config('ai.features.smart_search.temperature'));
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

        return $this->client->json([
            $this->systemMessage('You turn raw booking details into clear provider-friendly summaries.'),
            $this->userMessage(sprintf(
                $prompt,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            )),
        ], (float) config('ai.features.booking_helper.temperature'));
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

        return $this->client->json([
            $this->systemMessage('You rank providers using user need, area fit, and service relevance.'),
            $this->userMessage(sprintf(
                $prompt,
                json_encode($requestContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($providers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            )),
        ], (float) config('ai.features.provider_recommendations.temperature'));
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
}
