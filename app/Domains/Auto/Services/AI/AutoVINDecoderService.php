<?php declare(strict_types=1);

namespace App\Domains\Auto\Services\AI;

use OpenAI\Client as OpenAIClient;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\Log\LoggerInterface;

/**
 * AutoVINDecoderService - Decodes VIN numbers using AI
 * 
 * Extracts vehicle information from VIN codes.
 */
final readonly class AutoVINDecoderService
{
    public function __construct(
        private readonly OpenAIClient $openai,
        private readonly Cache $cache,
        private readonly LoggerInterface $logger,
        private readonly AutoDataAnonymizerService $anonymizer,
    ) {}

    /**
     * Decode VIN to extract vehicle information
     */
    public function decode(string $vin, string $correlationId): array
    {
        $cacheKey = "vin_decode:{$vin}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $response = $this->openai->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Decode this VIN: {$vin}. Extract: make, model, year, engine, transmission, drive type, body style. Return JSON with these exact keys.",
                ],
            ],
            'max_tokens' => 512,
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response->choices[0]->message->content ?? '{}';
        $decoded = json_decode($content, true);

        $anonymizedVin = $this->anonymizer->anonymizeVIN($vin);

        $this->logger->info('auto.vin_decode.completed', [
            'correlation_id' => $correlationId,
            'vin_anonymized' => $anonymizedVin,
        ]);

        if ($decoded === null || !is_array($decoded)) {
            $decoded = [
                'make' => 'Unknown',
                'model' => 'Unknown',
                'year' => 0,
                'engine' => 'Unknown',
                'transmission' => 'Unknown',
                'drive_type' => 'Unknown',
                'body_style' => 'Unknown',
            ];
        }

        $this->cache->put($cacheKey, $decoded, 86400);

        return $decoded;
    }
}
