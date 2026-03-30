<?php declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Services\PushNotificationService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Push Notification Channel - отправляет push-уведомления на мобильные устройства
 *
 * Поддерживает:
 * - Firebase Cloud Messaging (FCM)
 * - OneSignal
 * - Apple Push Notification (APN)
 */
class PushChannel
{
    /**
     * Инстанс PushNotificationService
     */
    protected PushNotificationService $pushService;

    /**
     * Конструктор
     */
    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Отправить push-уведомление
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Проверить, что объект имеет метод toFirebase
        if (!method_exists($notification, 'toFirebase')) {
            Log::warning('Notification does not have toFirebase method', [
                'notification_class' => get_class($notification),
                'notifiable_id' => $notifiable->id,
            ]);
            return;
        }

        try {
            // Получить FCM token или устройства пользователя
            $devices = $this->getDeviceTokens($notifiable);
            if (empty($devices)) {
                Log::debug('No device tokens found for user', [
                    'notifiable_id' => $notifiable->id,
                ]);
                return;
            }

            // Получить данные уведомления
            $pushData = $notification->toFirebase();

            // Отправить на каждое устройство
            foreach ($devices as $deviceToken) {
                try {
                    $this->pushService->send(
                        token: $deviceToken,
                        notification: $pushData,
                        correlationId: $notification->getCorrelationId(),
                        tenantId: $notification->getTenantId(),
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send push to device', [
                        'device_token' => substr($deviceToken, 0, 20) . '...',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::channel('audit')->info('Push notification sent', [
                'type' => $notification->getType(),
                'user_id' => $notifiable->id,
                'devices_count' => count($devices),
                'correlation_id' => $notification->getCorrelationId(),
                'tenant_id' => $notification->getTenantId(),
            ]);

        } catch (\Exception $e) {
            Log::channel('notifications')->error('Failed to send push notification', [
                'notification_class' => get_class($notification),
                'notifiable_id' => $notifiable->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить FCM токены устройств пользователя
     */
    protected function getDeviceTokens(object $notifiable): array
    {
        // Если у объекта есть отношение devices
        if (method_exists($notifiable, 'devices')) {
            return $notifiable->devices()
                ->where('push_enabled', true)
                ->pluck('fcm_token')
                ->filter()
                ->toArray();
        }

        // Если есть метод для получения токенов
        if (method_exists($notifiable, 'getPushTokens')) {
            return $notifiable->getPushTokens();
        }

        // Если есть прямое поле
        if (method_exists($notifiable, 'routeNotificationForPush')) {
            return (array) $notifiable->routeNotificationForPush();
        }

        return [];
    }
}
