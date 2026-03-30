<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SendChannelArchivedNotification extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public string $queue = 'notifications';

        public function __construct(
            private readonly NotificationService $notificationService,
        ) {}

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
                Log::channel('audit')->error('Failed to send channel archived notification', [
                    'correlation_id' => $event->correlationId,
                    'channel_id'     => $channel->id,
                    'error'          => $e->getMessage(),
                    'trace'          => $e->getTraceAsString(),
                ]);
            }
        }
}
