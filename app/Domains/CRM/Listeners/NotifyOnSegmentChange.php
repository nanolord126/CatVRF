<?php

declare(strict_types=1);

namespace App\Domains\CRM\Listeners;

use App\Domains\CRM\Events\CrmClientSegmentChanged;
use App\Domains\CRM\Jobs\SendCrmNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * NotifyOnSegmentChange — уведомление при смене сегмента клиента.
 *
 * Слушает CrmClientSegmentChanged.
 * При переходе в VIP — поздравительное письмо.
 * При переходе в at_risk/sleeping — реактивационная цепочка.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class NotifyOnSegmentChange implements ShouldQueue
{
    /**
     * Очередь для обработки.
     */

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(CrmClientSegmentChanged $event): void
    {
        $client = $event->client;

        $this->logger->info('CRM: client segment changed', [
            'client_id' => $client->id,
            'added_segments' => $event->addedSegments,
            'removed_segments' => $event->removedSegments,
            'correlation_id' => $event->correlationId,
        ]);

        foreach ($event->addedSegments as $segmentId) {
            $this->handleSegmentAdded($client, $segmentId, $event->correlationId);
        }
    }

    private function handleSegmentAdded(
        \App\Domains\CRM\Models\CrmClient $client,
        int $segmentId,
        string $correlationId,
    ): void {
        $segment = \App\Domains\CRM\Models\CrmSegment::find($segmentId);

        if ($segment === null) {
            return;
        }

        $slug = $segment->slug;

        if ($slug === 'vip') {
            SendCrmNotificationJob::dispatch(
                clientId: $client->id,
                channel: 'email',
                template: 'crm_vip_upgrade',
                data: [
                    'client_name' => $client->full_name,
                    'segment_name' => $segment->name,
                ],
                correlationId: $correlationId,
            );
        }

        if (in_array($slug, ['at_risk', 'sleeping'], true)) {
            SendCrmNotificationJob::dispatch(
                clientId: $client->id,
                channel: 'email',
                template: 'crm_reactivation',
                data: [
                    'client_name' => $client->full_name,
                    'segment_name' => $segment->name,
                    'days_inactive' => $client->last_order_at
                        ? now()->diffInDays($client->last_order_at)
                        : 999,
                ],
                correlationId: $correlationId,
            );
        }

        $this->logger->info('CRM: segment change notification dispatched', [
            'client_id' => $client->id,
            'segment_slug' => $slug,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Строковое представление для логирования.
     */
    public function __toString(): string
    {
        return 'NotifyOnSegmentChange';
    }
}
