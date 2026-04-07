<?php declare(strict_types=1);

namespace App\Services;



use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Illuminate\Log\LogManager;

/**
 * Push Notification Service - отправляет push через Firebase/OneSignal
 */
abstract class PushNotificationService
{
    /**
     * Firebase Messaging инстанс
     */
    private $messaging;

    /**
     * OneSignal API (альтернатива)
     */
    private readonly ?string $oneSignalAppId;
    private readonly ?string $oneSignalApiKey;

    /**
     * Конструктор
     */
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
    )
    {
        // Инициализировать Firebase если конфиг есть
        if ($this->config->get('services.firebase.credentials_path')) {
            try {
                $factory = new Factory();
                $firebase = $factory->withServiceAccount($this->config->get('services.firebase.credentials_path'));
                $this->messaging = $firebase->createMessaging();
            } catch (\Exception $e) {
                $this->logger->warning('Firebase not initialized', ['error' => $e->getMessage()]);
            }
        }

        $this->oneSignalAppId = $this->config->get('services.onesignal.app_id');
        $this->oneSignalApiKey = $this->config->get('services.onesignal.api_key');
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
                throw new \RuntimeException('Firebase not configured');
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

            $this->logger->channel('audit')->info('Push notification sent', [
                'token' => substr($token, 0, 20) . '...',
                'correlation_id' => $correlationId,
            ]);

            return true;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);

            $this->logger->error('Failed to send push notification', [
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
                    $this->logger->warning('Failed to send push to device', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}
