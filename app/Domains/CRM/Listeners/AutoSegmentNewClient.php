<?php

declare(strict_types=1);

namespace App\Domains\CRM\Listeners;

use App\Domains\CRM\Events\CrmClientCreated;
use App\Domains\CRM\Jobs\SendCrmNotificationJob;
use App\Domains\CRM\Services\CrmSegmentationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * AutoSegmentNewClient — автоматическая сегментация нового CRM-клиента.
 *
 * Слушает CrmClientCreated. При создании клиента:
 * 1. Определяет его сегмент (new, vip, loyal и т.д.)
 * 2. Отправляет welcome-уведомление
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class AutoSegmentNewClient implements ShouldQueue
{
    /**
     * Очередь для обработки.
     */

    public function __construct(
        private readonly CrmSegmentationService $segmentationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(CrmClientCreated $event): void
    {
        $this->logger->info('CRM: auto-segmenting new client', [
            'client_id' => $event->client->id,
            'tenant_id' => $event->tenantId,
            'correlation_id' => $event->correlationId,
        ]);

        $this->segmentationService->autoSegmentClient(
            $event->client,
            $event->correlationId,
        );

        SendCrmNotificationJob::dispatch(
            clientId: $event->client->id,
            channel: 'email',
            template: 'crm_welcome',
            data: [
                'client_name' => $event->client->full_name,
                'vertical' => $event->client->vertical,
            ],
            correlationId: $event->correlationId,
        );

        $this->logger->info('CRM: new client segmented and welcome notification queued', [
            'client_id' => $event->client->id,
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Строковое представление для логирования.
     */
    public function __toString(): string
    {
        return 'AutoSegmentNewClient';
    }
}
