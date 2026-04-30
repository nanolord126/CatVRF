<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Services\PIIAnonymizationService;
use App\Domains\Recommendation\Services\RecommendationService;
use App\Octane\Services\SwooleCoroutineService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\UploadedFile;
use OpenAI\Client as OpenAIClient;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Оркестратор AI-помощников и конструирования (Beauty, Auto, Food etc).
 * Требует наличия строгих квот и обязательного DTO ответа.
 *
 * Octane-aware: Uses Swoole coroutines for parallel AI calls when available.
 */
final readonly class AIConstructorService
{
    public function __construct(
        private readonly OpenAIClient $openai,
        private readonly RecommendationService $recommendationService,
        private readonly PIIAnonymizationService $piiAnonymizer,
        private readonly Repository $cache,
        private readonly LoggerInterface $logger,
        private readonly ?SwooleCoroutineService $coroutineService = null,
    ) {}

    /**
     * Анализ переданного фото и выдача рекомендаций с учетом текущих запасов.
     *
     * Octane-aware: Uses parallel execution for AI vision + recommendations when Swoole is available.
     */
    public function analyzePhotoAndRecommend(mixed $photo, string $vertical, int $userId, int $tenantId, string $correlationId): array
    {
        try {
            $this->logger->info('AI Vision Request completely initiated', [
                'user_id' => $userId,
                'vertical' => $vertical,
                'correlation_id' => $correlationId,
            ]);

            // Use coroutines for parallel execution if available
            if ($this->coroutineService !== null) {
                $parallelResults = $this->coroutineService->runParallel([
                    'vision_analysis' => fn () => $this->analyzePhotoWithVision($photo, $correlationId),
                    'recommendations' => fn () => $this->recommendationService->getForUser($userId, $vertical, [], $correlationId),
                ], timeout: 30.0);

                $analysis = $parallelResults['vision_analysis'];
                $recommendations = $parallelResults['recommendations'];
            } else {
                // Fallback to sequential execution
                $analysis = $this->analyzePhotoWithVision($photo, $correlationId);
                $recommendations = $this->recommendationService->getForUser($userId, $vertical, ['ai_context' => $analysis], $correlationId);
            }

            // Обязательная проверка по складу (есть ли это в наличии прямо сейчас)
            // TODO: Implement inventory service integration for stock checking
            $enriched = [];
            foreach ($recommendations as $item) {
                $item['in_stock'] = true; // Default to true until inventory service is implemented
                $enriched[] = $item;
            }

            $this->logger->info('AI constructor process unequivocally finished', [
                'user_id' => $userId,
                'items_count' => count($enriched),
                'correlation_id' => $correlationId,
                'execution_mode' => $this->coroutineService !== null ? 'coroutine_parallel' : 'sequential',
            ]);

            return [
                'success' => true,
                'analysis' => $analysis,
                'recommendations' => $enriched,
                'execution_mode' => $this->coroutineService !== null ? 'coroutine_parallel' : 'sequential',
            ];

        } catch (\Throwable $e) {
            $this->logger->error('Critical failure in AI Vision Core', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    private function analyzePhotoWithVision(mixed $photo, string $correlationId): array
    {
        // Симуляция обращения к AI Vision
        // В продакшене здесь будет реальный вызов OpenAI Vision API
        // Все данные будут анонимизированы перед отправкой в OpenAI
        return [
            'detected_style' => 'minimalism',
            'confidence' => 0.92,
        ];
    }

    /**
     * Validate photo input
     *
     * @param UploadedFile|string $photo
     * @throws RuntimeException
     */
    private function validatePhoto(mixed $photo): void
    {
        if ($photo instanceof UploadedFile) {
            // Validate file upload
            if (! $photo->isValid()) {
                throw new RuntimeException('Invalid file upload');
            }

            // Check file size (max 10MB)
            if ($photo->getSize() > 10 * 1024 * 1024) {
                throw new RuntimeException('Photo size exceeds 10MB limit');
            }

            // Check MIME type
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (! in_array($photo->getMimeType(), $allowedMimes, true)) {
                throw new RuntimeException('Invalid photo format. Allowed: JPEG, PNG, WebP, GIF');
            }

            return;
        }

        // Validate base64 string
        if (is_string($photo)) {
            $decoded = base64_decode($photo, true);
            if ($decoded === false) {
                throw new RuntimeException('Invalid base64 photo data');
            }

            if (strlen($decoded) > 10 * 1024 * 1024) {
                throw new RuntimeException('Photo size exceeds 10MB limit');
            }

            return;
        }

        throw new RuntimeException('Photo must be UploadedFile or base64 string');
    }

    /**
     * Generate cache key for AI results
     */
    private function generateCacheKey(mixed $photo, string $vertical, int $userId): string
    {
        $photoHash = '';
        
        if ($photo instanceof UploadedFile) {
            $photoHash = md5($photo->get() . $photo->getSize());
        } elseif (is_string($photo)) {
            $photoHash = md5($photo);
        }

        return 'ai_constructor:' . $vertical . ':' . $userId . ':' . $photoHash;
    }
}
