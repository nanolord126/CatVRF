<?php declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI;
use App\Services\Resilience\CircuitBreaker;
use App\Services\Resilience\RetryTrait;
use App\Services\Telemetry\ApiTelemetryService;
use Illuminate\Contracts\Cache\Repository;
use Psr\Log\LoggerInterface;

final class OpenAIClientService
{
    use RetryTrait;

    private mixed $client = null;
    private bool $isEnabled = false;
    private readonly CircuitBreaker $circuitBreaker;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Repository $cache
    ) {
        $this->circuitBreaker = new CircuitBreaker(
            cache: $this->cache,
            logger: $this->logger,
            service: 'openai',
            failureThreshold: 5,
            timeoutSeconds: 60,
            halfOpenMaxCalls: 3
        );

        $apiKey = config('services.openai.api_key', '');
        if ($apiKey !== '') {
            try {
                $clientClass = \OpenAI\Client::class;
                $this->client = $clientClass::factory()->withApiKey($apiKey)->make();
                $this->isEnabled = true;
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to initialize OpenAI client', ['error' => $e->getMessage()]);
                $this->isEnabled = false;
            }
        }
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function chat(array $messages, float $temperature = 0.3, string $responseFormat = 'text'): array
    {
        return $this->telemetry->withTelemetry(
            'openai',
            'chat',
            function () use ($messages, $temperature, $responseFormat) {
                return $this->circuitBreaker->call(function () use ($messages, $temperature, $responseFormat) {
                    return $this->executeWithRetry(
                        function () use ($messages, $temperature, $responseFormat) {
                            if (!$this->isEnabled) {
                                throw new \RuntimeException('OpenAI client is not configured or disabled.');
                            }

                            $responseFormat = $responseFormat === 'json'
                                ? ['type' => 'json_object']
                                : ['type' => 'text'];

                            $response = $this->client->chat()->create([
                                'model' => config('services.openai.model', 'gpt-4o'),
                                'messages' => $messages,
                                'temperature' => $temperature,
                                'response_format' => $responseFormat,
                            ]);

                            return [
                                'content' => $response->choices[0]->message->content,
                                'usage' => [
                                    'prompt_tokens' => $response->usage->promptTokens,
                                    'completion_tokens' => $response->usage->completionTokens,
                                    'total_tokens' => $response->usage->totalTokens,
                                ],
                            ];
                        },
                        maxAttempts: 3,
                        initialDelayMs: 200,
                        operationName: 'OpenAI chat'
                    );
                });
            }
        );
    }

    public function generateEmbedding(string $text): array
    {
        return $this->circuitBreaker->call(function () use ($text) {
            return $this->executeWithRetry(
                function () use ($text) {
                    if (!$this->isEnabled) {
                        throw new \RuntimeException('OpenAI client is not configured or disabled.');
                    }

                    $response = $this->client->embeddings()->create([
                        'model' => config('services.openai.embedding_model', 'text-embedding-3-small'),
                        'input' => $text,
                    ]);

                    return $response->embeddings[0]->embedding;
                },
                maxAttempts: 3,
                initialDelayMs: 200,
                operationName: 'OpenAI embedding'
            );
        });
    }
}
