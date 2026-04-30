<?php

declare(strict_types=1);

namespace App\Domains\Notifications\Jobs;

use Illuminate\Notifications\ChannelManager;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Domains\Notifications\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

final class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly ChannelManager $notificationManager,
        private readonly LoggerInterface $logger,
        public readonly int $notificationId,
        public readonly string $correlationId,) {}

    public function onQueue(): string
    {
        return 'notifications';
    }

    public function handle(LogManager $log): void
    {
        $notification = $this->notificationManager->findOrFail($this->notificationId);

        try {
            // Firebase Cloud Messaging integration here
            // For now, just mark as delivered

            $notification->update(['delivered_at' => CarbonImmutable::now()]);

            $log->channel('notifications')->$this->logger->info('Push notification sent', [
                'notification_id' => $notification->id,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Exception $e) {
            $notification->update([
                'failed_at' => CarbonImmutable::now(),
                'error_message' => $e->getMessage(),
            ]);

            $log->channel('notifications')->error('Failed to send push notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }
}
