<?php

namespace App\Services\AI;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class OpenAIResponsesClient
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $input
     */
    public function create(array $input, ?float $temperature = null): array
    {
        $apiKey = (string) config('services.openai.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is missing. Add it in your .env file before using AI features.');
        }

        $payload = [
            'model' => config('services.openai.model'),
            'input' => $input,
        ];

        if ($temperature !== null) {
            $payload['temperature'] = $temperature;
        }

        try {
            return $this->sendRequest($payload, $apiKey);
        } catch (RuntimeException $exception) {
            if ($temperature !== null && $this->isUnsupportedTemperatureError($exception->getMessage())) {
                unset($payload['temperature']);

                return $this->sendRequest($payload, $apiKey);
            }

            throw $exception;
        }
    }

    public function text(array $input, ?float $temperature = null): string
    {
        $response = $this->create($input, $temperature);

        return $this->extractText($response);
    }

    public function json(array $input, ?float $temperature = null): array
    {
        $text = $this->text($input, $temperature);
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI response was not valid JSON.');
        }

        return $decoded;
    }

    private function extractText(array $response): string
    {
        if (isset($response['output_text']) && is_string($response['output_text'])) {
            return trim($response['output_text']);
        }

        $chunks = [];

        foreach (($response['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                $candidate = $content['text']
                    ?? $content['output_text']
                    ?? null;

                if (is_string($candidate) && trim($candidate) !== '') {
                    $chunks[] = trim($candidate);
                }
            }
        }

        if ($chunks === []) {
            throw new RuntimeException('OpenAI response did not contain any readable text output.');
        }

        return trim(implode("\n", $chunks));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendRequest(array $payload, string $apiKey): array
    {
        try {
            $response = $this->http
                ->baseUrl((string) config('services.openai.base_url'))
                ->timeout((int) config('services.openai.timeout'))
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->post('/responses', $payload);

            return $response->throw()->json();
        } catch (RequestException $exception) {
            $message = $exception->response?->json('error.message');

            if (! is_string($message) || $message === '') {
                $message = $exception->getMessage();
            }

            throw new RuntimeException('OpenAI request failed: ' . $message, previous: $exception);
        } catch (ConnectionException $exception) {
            $message = $exception->getMessage();

            throw new RuntimeException('OpenAI request failed: ' . $message, previous: $exception);
        }
    }

    private function isUnsupportedTemperatureError(string $message): bool
    {
        return str_contains(strtolower($message), 'temperature')
            && str_contains(strtolower($message), 'not supported');
    }
}
