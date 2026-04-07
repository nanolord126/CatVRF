<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Jobs;

use App\Domains\VerticalName\Models\VerticalItem;
use App\Domains\VerticalName\Models\VerticalReview;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Job: пересчёт рейтинга VerticalItem на основе отзывов.
 *
 * CANON 2026 — Layer 6: Jobs.
 * Асинхронный пересчёт рейтинга товара.
 * correlation_id обязателен, audit log после выполнения.
 *
 * Запускается:
 * - После создания/обновления/удаления отзыва
 * - По расписанию (cron) для consistency check
 *
 * @package App\Domains\VerticalName\Jobs
 */
final class RecalculateVerticalItemRatingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly int $itemId,
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('vertical-name');
    }

    /**
     * Выполнение job'а: пересчёт рейтинга.
     *
     * 1. Выбираем все опубликованные отзывы.
     * 2. Считаем средний рейтинг.
     * 3. Обновляем модель.
     * 4. Audit log.
     */
    public function handle(
        AuditService $audit,
        LoggerInterface $logger,
    ): void {
        $item = VerticalItem::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->find($this->itemId);

        if ($item === null) {
            $logger->warning('VerticalName rating recalculation: item not found', [
                'item_id' => $this->itemId,
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        $reviews = VerticalReview::where('vertical_item_id', $this->itemId)
            ->where('is_published', true)
            ->get();

        $oldRating = $item->rating;
        $oldReviewCount = $item->review_count;

        $newReviewCount = $reviews->count();
        $newRating = $newReviewCount > 0
            ? round($reviews->avg('rating'), 2)
            : 0.0;

        $item->update([
            'rating' => $newRating,
            'review_count' => $newReviewCount,
            'correlation_id' => $this->correlationId,
        ]);

        $audit->record(
            action: 'vertical_name_rating_recalculated',
            subjectType: VerticalItem::class,
            subjectId: $this->itemId,
            oldValues: ['rating' => $oldRating, 'review_count' => $oldReviewCount],
            newValues: ['rating' => $newRating, 'review_count' => $newReviewCount],
            correlationId: $this->correlationId,
        );

        $logger->info('VerticalName item rating recalculated', [
            'item_id' => $this->itemId,
            'old_rating' => $oldRating,
            'new_rating' => $newRating,
            'review_count' => $newReviewCount,
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Уникальный ID job'а для предотвращения дубликатов.
     */
    public function uniqueId(): string
    {
        return 'vertical_name_rating:' . $this->itemId . ':' . $this->tenantId;
    }
}
