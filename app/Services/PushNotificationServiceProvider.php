<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

/**
 * Push Notification Service - отправляет push через Firebase/OneSignal
 */
class PushNotificationService
{
    /**
     * Firebase Messaging инстанс
     */
    protected $messaging;

    /**
     * OneSignal API (альтернатива)
     */
    protected ?string $oneSignalAppId;
    protected ?string $oneSignalApiKey;

    /**
     * Конструктор
     */
    public function __construct()
    {
        // Инициализировать Firebase если конфиг есть
        if (config('services.firebase.credentials_path')) {
            try {
                $factory = new Factory();
                $firebase = $factory->withServiceAccount(config('services.firebase.credentials_path'));
                $this->messaging = $firebase->createMessaging();
            } catch (\Exception $e) {
                Log::warning('Firebase not initialized', ['error' => $e->getMessage()]);
            }
        }

        $this->oneSignalAppId = config('services.onesignal.app_id');
        $this->oneSignalApiKey = config('services.onesignal.api_key');
    }

    /**
     * Отправить push через Firebase
     */
    public function send(
        string $token,
        array $notification,
        ?string $correlationId = null,
        ?int $tenantId = null
    ): bool {
        try {
            if (!isset($this->messaging)) {
                throw new \Exception('Firebase not configured');
            }

            // Создать облако сообщение
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(FirebaseNotification->create(
                    $notification['notification']['title'] ?? 'Notification',
                    $notification['notification']['body'] ?? ''
                ))
                ->withData($notification['data'] ?? []);

            // Отправить
            $this->messaging->send($message);

            Log::channel('audit')->info('Push notification sent', [
                'token' => substr($token, 0, 20) . '...',
                'correlation_id' => $correlationId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    /**
     * Отправить уведомление (для NotificationService)
     */
    public function sendNotification($notification, $user, string $correlationId): void
    {
        if (method_exists($notification, 'toFirebase')) {
            // Получить FCM токены
            $tokens = $user->devices()
                ->where('push_enabled', true)
                ->pluck('fcm_token')
                ->filter()
                ->toArray();

            foreach ($tokens as $token) {
                try {
                    $this->send(
                        token: $token,
                        notification: $notification->toFirebase(),
                        correlationId: $correlationId,
                        tenantId: $user->tenant_id ?? null,
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send push to device', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}
