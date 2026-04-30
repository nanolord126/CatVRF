<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\UseriMessageLink;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

final readonly class iMessageService
{
    private string $apnsKeyId;
    private string $apnsTeamId;
    private string $apnsPrivateKey;
    private string $apnsBundleId;
    private bool $useSandbox;

    public function __construct()
    {
        $this->apnsKeyId = config('services.apns.key_id', '');
        $this->apnsTeamId = config('services.apns.team_id', '');
        $this->apnsPrivateKey = config('services.apns.private_key', '');
        $this->apnsBundleId = config('services.apns.bundle_id', '');
        $this->useSandbox = config('services.apns.sandbox', true);
    }

    /**
     * Send order notification via APNs (iMessage)
     */
    public function sendOrderNotification(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UseriMessageLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $recipientType = $link->user_id === $buyerId ? 'buyer' : 'seller';
            $title = $this->buildTitle($orderId, $eventType, $recipientType);
            $body = $this->buildBody($orderId, $eventType, $recipientType, $data);

            try {
                $this->sendAPNsNotification(
                    $link->device_token,
                    $title,
                    $body,
                    array_merge($data, [
                        'order_id' => $orderId,
                        'type' => 'order_update',
                        'url' => url("/orders/{$orderId}"),
                    ])
                );
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Failed to send iMessage notification', [
                    'device_token' => $link->device_token,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send notification to specific user via iMessage
     */
    public function sendNotificationToUser(int $userId, string $title, string $body, array $data = []): bool
    {
        $links = UseriMessageLink::where('user_id', $userId)
            ->active()
            ->verified()
            ->get();

        if ($links->isEmpty()) {
            return false;
        }

        $sent = false;
        foreach ($links as $link) {
            try {
                $this->sendAPNsNotification(
                    $link->device_token,
                    $title,
                    $body,
                    $data
                );
                $link->markAsNotified();
                $sent = true;
            } catch (\Throwable $e) {
                Log::error('Failed to send iMessage notification to user', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    /**
     * Send notification via Apple Push Notification Service
     */
    private function sendAPNsNotification(
        string $deviceToken,
        string $title,
        string $body,
        array $data = []
    ): void {
        $url = $this->useSandbox
            ? 'https://api.development.push.apple.com/3/device/' . $deviceToken
            : 'https://api.push.apple.com/3/device/' . $deviceToken;

        $payload = [
            'aps' => [
                'alert' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'sound' => 'default',
                'badge' => 1,
            ],
            'data' => $data,
        ];

        // Generate JWT token for APNs authentication
        $jwt = $this->generateAPNsJWT();

        $response = Http::withHeaders([
            'apns-topic' => $this->apnsBundleId,
            'apns-push-type' => 'alert',
            'apns-priority' => '10',
            'Authorization' => "bearer {$jwt}",
        ])->post($url, $payload);

        if (!$response->successful()) {
            Log::error('APNs API error', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);
        }
    }

    /**
     * Generate JWT token for APNs authentication
     */
    private function generateAPNsJWT(): string
    {
        // In production, use firebase/php-jwt or lcobucci/jwt library
        // This is a simplified implementation
        
        $header = [
            'alg' => 'ES256',
            'kid' => $this->apnsKeyId,
        ];

        $payload = [
            'iss' => $this->apnsTeamId,
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        // Note: Actual JWT generation requires ECDSA signing with the private key
        // This is a placeholder for the implementation
        Log::warning('APNs JWT generation requires proper ECDSA signing library', [
            'key_id' => $this->apnsKeyId,
            'team_id' => $this->apnsTeamId,
        ]);

        return 'placeholder_jwt_token';
    }

    /**
     * Register device token for iMessage notifications
     */
    public function registerDevice(
        int $userId,
        string $deviceToken,
        string $deviceType = 'iphone',
        string $deviceId = null
    ): UseriMessageLink {
        return UseriMessageLink::updateOrCreate(
            [
                'user_id' => $userId,
                'device_token' => $deviceToken,
            ],
            [
                'device_id' => $deviceId,
                'device_type' => $deviceType,
                'is_active' => true,
            ]
        );
    }

    /**
     * Unregister device token
     */
    public function unregisterDevice(int $userId, string $deviceToken): bool
    {
        $link = UseriMessageLink::where('user_id', $userId)
            ->where('device_token', $deviceToken)
            ->first();

        if (!$link) {
            return false;
        }

        $link->deactivate();
        return true;
    }

    /**
     * Unregister all devices for user
     */
    public function unregisterAll(int $userId): int
    {
        return UseriMessageLink::where('user_id', $userId)
            ->update(['is_active' => false]);
    }

    private function buildTitle(int $orderId, string $eventType, string $recipientType): string
    {
        if ($eventType === 'created') {
            return $recipientType === 'buyer'
                ? "✅ Заказ №{$orderId} оформлен"
                : "🛒 Новый заказ №{$orderId}";
        }

        return match($eventType) {
            'confirmed' => "✅ Заказ №{$orderId} подтверждён",
            'ready_for_delivery' => "📦 Заказ №{$orderId} готов к выдаче",
            'in_delivery' => "🚚 Курьер в пути! Заказ №{$orderId}",
            'delivered' => "🎉 Заказ №{$orderId} доставлен",
            'cancelled' => "❌ Заказ №{$orderId} отменён",
            default => "Обновление заказа №{$orderId}",
        };
    }

    private function buildBody(int $orderId, string $eventType, string $recipientType, array $data): string
    {
        $amount = $data['amount'] ?? '0';
        $eta = $data['eta'] ?? '30';

        if ($eventType === 'created') {
            return $recipientType === 'buyer'
                ? "Сумма: {$amount} ₽"
                : "Сумма: {$amount} ₽";
        }

        return match($eventType) {
            'confirmed' => "Товары зарезервированы",
            'ready_for_delivery' => "Пожалуйста, подтвердите в панели",
            'in_delivery' => "Примерное время: {$eta} минут",
            'delivered' => "Оцените ваш заказ",
            'cancelled' => "Причина: " . ($data['reason'] ?? 'Не указана'),
            default => "Статус обновлён",
        };
    }
}
