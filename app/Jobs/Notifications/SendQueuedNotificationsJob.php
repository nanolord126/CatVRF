<?php declare(strict_types=1);

namespace App\Jobs\Notifications;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class SendQueuedNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('notifications');
    }

    public function tags(): array
    {
        return ['notification', 'push', 'email', 'sms'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    public function handle(NotificationService $notificationService): void
    {
        try {
            DB::transaction(function () use ($notificationService) {
                $queuedNotifications = $notificationService->getQueuedNotifications(limit: 100);

                foreach ($queuedNotifications as $notification) {
                    try {
                        $notificationService->send($notification, $this->correlationId);

                        Log::channel('audit')->info('Notification sent', [
                            'correlation_id' => $this->correlationId,
                            'notification_id' => $notification->id,
                            'user_id' => $notification->user_id,
                            'channel' => $notification->channel,
                        ]);
                    } catch (\Exception $e) {
                        if ($notification->retry_count < 3) {
                            $notificationService->incrementRetry($notification->id);

                            Log::channel('audit')->warning('Notification retry scheduled', [
                                'correlation_id' => $this->correlationId,
                                'notification_id' => $notification->id,
                                'retry_count' => $notification->retry_count + 1,
                                'error' => $e->getMessage(),
                            ]);
                        } else {
                            $notificationService->markFailed($notification->id, $e->getMessage());

                            Log::channel('audit')->error('Notification failed after retries', [
                                'correlation_id' => $this->correlationId,
                                'notification_id' => $notification->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Notification batch job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
