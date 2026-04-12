<?php

declare(strict_types=1);

namespace App\Domains\CRM\Listeners;

use App\Domains\CRM\Events\CrmInteractionRecorded;
use App\Domains\CRM\Services\CrmService;
use App\Domains\CRM\Services\CrmSegmentationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * RecalculateClientOnInteraction — пересчёт статистики клиента и ре-сегментация
 * после записи нового взаимодействия.
 *
 * Слушает CrmInteractionRecorded. При каждом новом interaction:
 * 1. Пересчитывает статистику клиента (total_orders, total_spent и т.д.)
 * 2. Проверяет, нужно ли перенести клиента в другой сегмент
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class RecalculateClientOnInteraction implements ShouldQueue
{
    /**
     * Очередь для обработки.
     */

    public function __construct(
        private readonly CrmService $crmService,
        private readonly CrmSegmentationService $segmentationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(CrmInteractionRecorded $event): void
    {
        $interaction = $event->interaction;
        $client = $interaction->client;

        if ($client === null) {
            $this->logger->warning('CRM: interaction has no client, skipping recalculation', [
                'interaction_id' => $interaction->id,
                'correlation_id' => $event->correlationId,
            ]);

            return;
        }

        $this->logger->info('CRM: recalculating client stats after interaction', [
            'client_id' => $client->id,
            'interaction_type' => $interaction->type,
            'correlation_id' => $event->correlationId,
        ]);

        $this->crmService->recalculateClientStats($client, $event->correlationId);

        $this->segmentationService->autoSegmentClient($client->fresh(), $event->correlationId);

        $this->logger->info('CRM: client stats recalculated and re-segmented', [
            'client_id' => $client->id,
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Строковое представление для логирования.
     */
    public function __toString(): string
    {
        return 'RecalculateClientOnInteraction';
    }
}
