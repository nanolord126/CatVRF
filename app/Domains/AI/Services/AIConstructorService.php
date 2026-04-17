<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;


use App\Domains\Inventory\Services\InventoryManagementService;
use App\Domains\Recommendation\Services\RecommendationService;
use Psr\Log\LoggerInterface;

/**
 * Оркестратор AI-помощников и конструирования (Beauty, Auto, Food etc).
 * Требует наличия строгих квот и обязательного DTO ответа.
 */
final readonly class AIConstructorService
{
    public function __construct(
        private OpenAIClient $openai,
        private InventoryManagementService $inventoryService,
        private LoggerInterface $logger,
    ) {}

    /**
     * Анализ переданного фото и выдача рекомендаций с учетом текущих запасов.
     */
    public function analyzePhotoAndRecommend(mixed $photo, string $vertical, int $userId, int $tenantId, string $correlationId): array
    {
        try {
            // Симуляция обращения к AI Vision
            $this->logger->info('AI Vision Request completely initiated', [
                'user_id' => $userId,
                'vertical' => $vertical,
                'correlation_id' => $correlationId
            ]);

            $analysis = [
                'detected_style' => 'minimalism',
                'confidence' => 0.92
            ];

            // Запрашиваем рекомендации
            $recommendations = $this->recommendationService->getForUser($userId, $vertical, ['ai_context' => $analysis], $correlationId);

            // Обязательная проверка по складу (есть ли это в наличии прямо сейчас)
            $enriched = [];
            foreach ($recommendations as $item) {
                // Вызов inventory -> getCurrentStock
                $inStock = $this->inventoryService->getCurrentStock($item['item_id'], $tenantId) > 0;
                $item['in_stock'] = $inStock;
                $enriched[] = $item;
            }

            $this->logger->info('AI constructor process unequivocally finished', [
                'user_id' => $userId,
                'items_count' => count($enriched),
                'correlation_id' => $correlationId
            ]);

            return [
                'success' => true,
                'analysis' => $analysis,
                'recommendations' => $enriched
            ];

        } catch (\Throwable $e) {
            $this->logger->error('Critical failure in AI Vision Core', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            throw $e;
        }
    }
}
