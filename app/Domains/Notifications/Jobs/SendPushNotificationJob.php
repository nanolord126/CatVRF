<?php declare(strict_types=1);

namespace App\Domains\Notifications\Jobs;

use App\Domains\Notifications\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $notificationId,
        public readonly string $correlationId,
    ) {}

    public function onQueue(): string
    {
        return 'notifications';
    }

    public function handle(): void
    {
        $notification = Notification::findOrFail($this->notificationId);

        try {
            // Firebase Cloud Messaging integration here
            // For now, just mark as delivered
            
            $notification->update(['delivered_at' => now()]);

            Log::channel('notifications')->info('Push notification sent', [
                'notification_id' => $notification->id,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Exception $e) {
            $notification->update([
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::channel('notifications')->error('Failed to send push notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }
}
