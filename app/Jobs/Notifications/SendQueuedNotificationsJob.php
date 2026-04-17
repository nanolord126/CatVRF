<?php declare(strict_types=1);

namespace App\Jobs\Notifications;


use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final class SendQueuedNotificationsJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    private string $correlationId;

    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    )
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
            $this->db->transaction(function () use ($notificationService) {
                $queuedNotifications = $notificationService->getQueuedNotifications(limit: 100);

                foreach ($queuedNotifications as $notification) {
                    try {
                        $notificationService->send($notification, $this->correlationId);

                        $this->logger->channel('audit')->info('Notification sent', [
                            'correlation_id' => $this->correlationId,
                            'notification_id' => $notification->id,
                            'user_id' => $notification->user_id,
                            'channel' => $notification->channel,
                        ]);
                    } catch (\Exception $e) {
                        if ($notification->retry_count < 3) {
                            $notificationService->incrementRetry($notification->id);

                            $this->logger->channel('audit')->warning('Notification retry scheduled', [
                                'correlation_id' => $this->correlationId,
                                'notification_id' => $notification->id,
                                'retry_count' => $notification->retry_count + 1,
                                'error' => $e->getMessage(),
                            ]);
                        } else {
                            $notificationService->markFailed($notification->id, $e->getMessage());

                            $this->logger->channel('audit')->error('Notification failed after retries', [
                                'correlation_id' => $this->correlationId,
                                'notification_id' => $notification->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->channel('audit')->error('Notification batch job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

