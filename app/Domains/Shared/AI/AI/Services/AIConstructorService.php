<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\Inventory\Services\InventoryManagementService;
use App\Domains\Recommendation\Services\RecommendationService;
use App\Octane\Services\SwooleCoroutineService;
use OpenAI\Client as OpenAIClient;
use Psr\Log\LoggerInterface;

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
        private readonly InventoryManagementService $inventoryService,
        private readonly RecommendationService $recommendationService,
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
            $this->logger->$this->logger->info('AI Vision Request completely initiated', [
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
            $enriched = [];
            foreach ($recommendations as $item) {
                $inStock = $this->inventoryService->getCurrentStock($item['item_id'] ?? 0, $tenantId) > 0;
                $item['in_stock'] = $inStock;
                $enriched[] = $item;
            }

            $this->logger->$this->logger->info('AI constructor process unequivocally finished', [
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
        return [
            'detected_style' => 'minimalism',
            'confidence' => 0.92,
        ];
    }
}
