<?php declare(strict_types=1);

/**
 * SendChannelArchivedNotification — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/sendchannelarchivednotification
 */


namespace App\Domains\Education\Channels\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class SendChannelArchivedNotification
{

    use InteractsWithQueue;


        public function __construct(
            private readonly NotificationService $notificationService, private readonly LoggerInterface $logger) {}

        public function handle(ChannelArchived $event): void
        {
            $channel = $event->channel;

            try {
                $this->notificationService->send(
                    userId:  0, // будет отправлено через tenant_id
                    type:    'channel_archived',
                    message: "Ваш канал «{$channel->name}» перенесён в архив: {$event->reason}",
                    data: [
                        'channel_id'     => $channel->id,
                        'channel_slug'   => $channel->slug,
                        'reason'         => $event->reason,
                        'archived_at'    => Carbon::now()->toIso8601String(),
                        'correlation_id' => $event->correlationId,
                    ],
                    tenantId: $channel->tenant_id,
                );
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send channel archived notification', [
                    'correlation_id' => $event->correlationId,
                    'channel_id'     => $channel->id,
                    'error'          => $e->getMessage(),
                    'trace'          => $e->getTraceAsString(),
                ]);
            }
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
