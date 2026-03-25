declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Channels\Listeners;

use App\Domains\Channels\Events\ChannelArchived;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Уведомить владельца бизнеса о том, что канал архивирован.
 */
final class SendChannelArchivedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

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
                    'archived_at'    => now()->toIso8601String(),
                    'correlation_id' => $event->correlationId,
                ],
                tenantId: $channel->tenant_id,
            );
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to send channel archived notification', [
                'correlation_id' => $event->correlationId,
                'channel_id'     => $channel->id,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);
        }
    }
}
