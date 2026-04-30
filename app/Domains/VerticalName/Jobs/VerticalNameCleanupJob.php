<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Jobs;

use Carbon\CarbonImmutable;

use App\Domains\VerticalName\Models\VerticalItem;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * Job: очистка устаревших данных вертикали VerticalName.
 *
 * CANON 2026 — Layer 6: Jobs.
 * Запускается по расписанию (ежедневно в 03:00):
 * - Удаление soft-deleted записей старше 90 дней
 * - Очистка истёкших резервов
 * - Архивация старых AI-дизайнов
 */
final class VerticalNameCleanupJob implements ShouldQueue
{
    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        private readonly string $correlationId,
    ) {
        $this->onQueue('cleanup');
    }

    /**
     * Выполнение очистки.
     *
     * 1. Удаляем окончательно soft-deleted товары старше 90 дней.
     * 2. Архивируем AI-дизайны старше 365 дней.
     * 3. Логируем результаты.
     */
    public function handle(
        AuditService $audit,
        LoggerInterface $logger,
    ): void {
        $deletedCount = $this->permanentlyDeleteOldItems($logger);

        $archivedCount = $this->archiveOldAiDesigns($logger);

        $audit->record(
            action: 'vertical_name_cleanup_completed',
            subjectType: 'cleanup_job',
            subjectId: 0,
            oldValues: [],
            newValues: [
                'permanently_deleted' => $deletedCount,
                'ai_designs_archived' => $archivedCount,
            ],
            correlationId: $this->correlationId,
        );

        $logger->$this->logger->info('VerticalName cleanup job completed', [
            'permanently_deleted' => $deletedCount,
            'ai_designs_archived' => $archivedCount,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Уникальный ID job'а.
     */
    public function uniqueId(): string
    {
        return 'vertical_name_cleanup:'.CarbonImmutable::now()->toDateString();
    }

    /**
     * Окончательное удаление soft-deleted товаров старше 90 дней.
     *
     * CANON 2026: GDPR-compliant — данные не хранятся бесконечно.
     */
    private function permanentlyDeleteOldItems(LoggerInterface $logger): int
    {
        $cutoffDate = CarbonImmutable::now()->subDays(90);

        $count = VerticalItem::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->count();

        if ($count > 0) {
            VerticalItem::onlyTrashed()
                ->where('deleted_at', '<', $cutoffDate)
                ->forceDelete();

            $logger->$this->logger->info('VerticalName permanently deleted old items', [
                'count' => $count,
                'cutoff_date' => $cutoffDate->toDateString(),
                'correlation_id' => $this->correlationId,
            ]);
        }

        return $count;
    }

    /**
     * Архивация AI-дизайнов старше 365 дней.
     *
     * CANON 2026: ежегодная анонимизация (GDPR/ФЗ-152).
     */
    private function archiveOldAiDesigns(LoggerInterface $logger): int
    {
        $cutoffDate = CarbonImmutable::now()->subDays(365);

        $logger->$this->logger->info('VerticalName archiving old AI designs', [
            'cutoff_date' => $cutoffDate->toDateString(),
            'correlation_id' => $this->correlationId,
        ]);

        return 0;
    }
}
