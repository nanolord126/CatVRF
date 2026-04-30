<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use App\Models\Notification as NotificationModel;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
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
    public function __construct(private readonly LoggerInterface $logger,
        private readonly int $notificationId,
        private readonly string $correlationId,
        private readonly int $tenantId,
        private readonly LogManager $logger,
        private readonly \App\Services\EmailService $emailService,
        private readonly \App\Services\SmsService $smsService,
        private readonly \App\Services\PushNotificationService $pushService,) {
        // Добавить теги для мониторинга
        $this->onQueue('notification');
        $this->withTags([
            'notification',
            "tenant:{$this->tenantId}",
            "correlation:{$this->correlationId}",
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
                $this->logger->$this->logger->info('Notification already processed', [
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

                $this->logger->channel('audit')->$this->logger->info('Notification sent successfully', [
                    'notification_id' => $this->notificationId,
                    'user_id' => $user->id,
                    'channels' => $notification->channels,
                    'correlation_id' => $this->correlationId,
                ]);
            } else {
                // Все каналы неудачны - переход на retry
                throw new \RuntimeException('All channels failed: '.json_encode($failedChannels));
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

    /**
     * Отправить на один канал
     */
    protected function sendToChannel(NotificationModel $notification, User $user, string $channel): void
    {
        match($channel) {
            'email' => $this->emailService->sendNotification(
                $notification,
                $user,
                $this->correlationId
            ),
            'sms' => $this->smsService->sendNotification(
                $notification,
                $user,
                $this->correlationId
            ),
            'push' => $this->pushService->sendNotification(
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
}
