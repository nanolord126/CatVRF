<?php declare(strict_types=1);

namespace App\Domains\Auto\Services\AI;

use Illuminate\Http\UploadedFile;
use OpenAI\Client as OpenAIClient;
use Psr\Log\LoggerInterface;

/**
 * AutoVisionAnalysisService - Analyzes car photos for damage assessment
 * 
 * Uses OpenAI vision to detect exterior damage, tire condition, glass condition, etc.
 */
final readonly class AutoVisionAnalysisService
{
    public function __construct(
        private readonly OpenAIClient $openai,
        private readonly LoggerInterface $logger,
        private readonly AutoDataAnonymizerService $anonymizer,
    ) {}

    /**
     * Analyze car photo for damage assessment
     */
    public function analyzePhoto(UploadedFile $photo, string $vin, string $correlationId): array
    {
        $anonymizedVin = $this->anonymizer->anonymizeVIN($vin);
        $imageData = base64_encode(file_get_contents($photo->getRealPath()));

        $response = $this->openai->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "Analyze this car photo for damage assessment. VIN reference: {$anonymizedVin}. Identify: 1) Exterior damage (scratches, dents, rust), 2) Tire condition, 3) Glass condition, 4) Light functionality, 5) Overall condition rating (1-10). Return JSON with structure: {\"damages\": [{\"location\": \"\", \"type\": \"\", \"severity\": \"low|medium|high\", \"description\": \"\"}], \"tires\": {\"front_left\": \"\", \"front_right\": \"\", \"rear_left\": \"\", \"rear_right\": \"\"}, \"glass\": {\"windshield\": \"\", \"windows\": \"\"}, \"lights\": {\"headlights\": \"\", \"taillights\": \"\"}, \"overall_condition\": 8}",
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => ['url' => "data:image/jpeg;base64,$imageData"],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 2048,
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response->choices[0]->message->content ?? '{}';
        $analysis = json_decode($content, true);

        if ($analysis === null || !is_array($analysis)) {
            throw new \RuntimeException('Failed to parse AI vision analysis response');
        }

        $this->logger->info('auto.vision_analysis.completed', [
            'vin_anonymized' => $anonymizedVin,
            'correlation_id' => $correlationId,
            'damages_count' => count($analysis['damages'] ?? []),
        ]);

        return $analysis;
    }
}
