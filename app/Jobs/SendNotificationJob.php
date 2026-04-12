<?php declare(strict_types=1);

namespace App\Jobs;


use App\Models\Notification as NotificationModel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;


/**
 * SendNotificationJob - асинхронная отправка уведомлений через Queue
 *
 * Обязательные поля:
 * - notification_id: ID уведомления из БД
 * - correlation_id: UUID для трейсинга
 * - tenant_id: ID тенанта
 *
 * Повторы: максимум 3 попытки с задержкой 5 минут
 */
final class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * ID уведомления
     */
    private int $notificationId;

    /**
     * Correlation ID для трейсинга
     */
    private string $correlationId;

    /**
     * Tenant ID
     */
    private int $tenantId;

    /**
     * Максимум попыток отправки
     */
    public int $tries = 3;

    /**
     * Макс время жизни job (сек)
     */
    public int $timeout = 300;

    /**
     * Задержка между попытками (сек)
     */
    public int $backoff = 300; // 5 минут

    /**
     * Конструктор
     */
    public function __construct(int $notificationId, string $correlationId, int $tenantId,
        private readonly LogManager $logger,
    )
    {
        $this->notificationId = $notificationId;
        $this->correlationId = $correlationId;
        $this->tenantId = $tenantId;

        // Добавить теги для мониторинга
        $this->onQueue('notifications');
        $this->withTags([
            'notification',
            "tenant:{$tenantId}",
            "correlation:{$correlationId}",
        ]);
    }

    /**
     * Выполнить отправку уведомления
     */
    public function handle(): void
    {
        try {
            // Загрузить уведомление из БД
            $notification = NotificationModel::findOrFail($this->notificationId);

            // Проверить статус (может уже быть отправлено)
            if ($notification->status !== 'pending') {
                $this->logger->info('Notification already processed', [
                    'notification_id' => $this->notificationId,
                    'status' => $notification->status,
                ]);
                return;
            }

            // Загрузить пользователя
            $user = User::findOrFail($notification->user_id);

            // Отправить на каждый канал
            $failedChannels = [];
            foreach ($notification->channels as $channel) {
                try {
                    $this->sendToChannel($notification, $user, $channel);
                } catch (\Exception $e) {
                    $failedChannels[$channel] = $e->getMessage();
                    $this->logger->warning("Failed to send to $channel", [
                        'notification_id' => $this->notificationId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Если хоть одно успешно - отметить как sent
            if (empty($failedChannels) || count($failedChannels) < count($notification->channels)) {
                $notification->markAsSent();

                $this->logger->channel('audit')->info('Notification sent successfully', [
                    'notification_id' => $this->notificationId,
                    'user_id' => $user->id,
                    'channels' => $notification->channels,
                    'correlation_id' => $this->correlationId,
                ]);
            } else {
                // Все каналы неудачны - переход на retry
                throw new \RuntimeException('All channels failed: ' . json_encode($failedChannels));
            }

        } catch (\Exception $e) {
            $this->logger->warning('SendNotificationJob failed', [
                'notification_id' => $this->notificationId,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'error' => $e->getMessage(),
            ]);

            // Если это последняя попытка - отметить как failed
            if ($this->isReleased()) {
                $this->handleJobFailure($e);
            } else {
                // Переотправить с задержкой
                $this->release($this->backoff);
            }
        }
    }

    /**
     * Отправить на один канал
     */
    protected function sendToChannel(NotificationModel $notification, User $user, string $channel): void
    {
        match($channel) {
            'email' => app('App\Services\EmailService')->sendNotification(
                $notification,
                $user,
                $this->correlationId
            ),
            'sms' => app('App\Services\SmsService')->sendNotification(
                $notification,
                $user,
                $this->correlationId
            ),
            'push' => app('App\Services\PushNotificationService')->sendNotification(
                $notification,
                $user,
                $this->correlationId
            ),
            'database' => null, // Already saved
            'web' => null, // Handle via broadcaster
            default => throw new \InvalidArgumentException("Unknown channel: $channel"),
        };
    }

    /**
     * Обработка финального отказа job
     */
    protected function handleJobFailure(\Throwable $exception): void
    {
        try {
            $notification = NotificationModel::find($this->notificationId);
            if ($notification) {
                $notification->markAsFailed($exception->getMessage());
            }

            $this->logger->channel('notifications')->error('Notification delivery failed after retries', [
                'notification_id' => $this->notificationId,
                'attempts' => $this->attempts(),
                'error' => $exception->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle notification failure', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Job не удалось выполнить после всех попыток
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleJobFailure($exception);
    }

    /**
     * Получить display name для Queue
     */
    public function displayName(): string
    {
        return "SendNotification#{$this->notificationId}";
    }
}
