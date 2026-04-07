<?php

declare(strict_types=1);

/**
 * FlowerReviewService — CatVRF 2026 Component.
 *
 * Сервис управления отзывами на цветочные магазины и букеты.
 * Все мутации через DB::transaction + fraud-check + correlation_id.
 *
 * @package App\Domains\Flowers\Services
 * @version 2026.1
 */

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\FlowerReview;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

final readonly class FlowerReviewService
{
    /**
     * Идентификатор версии компонента.
     */
    private const VERSION = '1.0.0';

    /**
     * Максимальное количество повторных попыток.
     */
    private const MAX_RETRIES = 3;

    /**
     * TTL кэша по умолчанию (секунды).
     */
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Создать отзыв на цветочный магазин или букет.
     *
     * @param array  $data          Данные отзыва (rating, text, shop_id и т.д.)
     * @param int    $userId        ID автора отзыва (для fraud-check)
     * @param string $correlationId Трейсинг-идентификатор
     */
    public function createReview(array $data, int $userId, string $correlationId): FlowerReview
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'flower_review_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $correlationId): FlowerReview {
            $review = FlowerReview::create(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            $this->logger->info('Flower review created', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        });
    }
}
