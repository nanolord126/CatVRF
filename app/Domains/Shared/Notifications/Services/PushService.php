<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\UserPushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class PushService
{
    private string $fcmServerKey;
    private string $fcmProjectId;
    private string $webPushVapidPublicKey;
    private string $webPushVapidPrivateKey;

    public function __construct()
    {
        $this->fcmServerKey = config('services.fcm.server_key', '');
        $this->fcmProjectId = config('services.fcm.project_id', '');
        $this->webPushVapidPublicKey = config('services.web_push.vapid_public_key', '');
        $this->webPushVapidPrivateKey = config('services.web_push.vapid_private_key', '');
    }

    /**
     * Send push notification for order events
     */
    public function sendOrderNotification(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $subscriptions = UserPushSubscription::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->get();

        foreach ($subscriptions as $subscription) {
            $recipientType = $subscription->user_id === $buyerId ? 'buyer' : 'seller';
            $title = $this->buildTitle($orderId, $eventType, $recipientType);
            $body = $this->buildBody($orderId, $eventType, $recipientType, $data);

            try {
                if ($subscription->device_type === 'web') {
                    $this->sendWebPush($subscription, $title, $body, $data);
                } else {
                    $this->sendFCM($subscription, $title, $body, $data);
                }
                $subscription->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Failed to send push notification', [
                    'subscription_id' => $subscription->id,
                    'device_type' => $subscription->device_type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send push notification to specific user
     */
    public function sendNotificationToUser(int $userId, string $title, string $body, array $data = []): bool
    {
        $subscriptions = UserPushSubscription::where('user_id', $userId)
            ->active()
            ->get();

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $sent = false;
        foreach ($subscriptions as $subscription) {
            try {
                if ($subscription->device_type === 'web') {
                    $this->sendWebPush($subscription, $title, $body, $data);
                } else {
                    $this->sendFCM($subscription, $title, $body, $data);
                }
                $subscription->markAsNotified();
                $sent = true;
            } catch (\Throwable $e) {
                Log::error('Failed to send push notification to user', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    /**
     * Send notification via Firebase Cloud Messaging (iOS/Android)
     */
    private function sendFCM(
        UserPushSubscription $subscription,
        string $title,
        string $body,
        array $data = []
    ): void {
        $response = Http::withHeaders([
            'Authorization' => 'key=' . $this->fcmServerKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $subscription->endpoint,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => array_merge($data, [
                'order_id' => $data['order_id'] ?? null,
                'type' => 'order_update',
            ]),
        ]);

        if (!$response->successful()) {
            Log::error('FCM API error', [
                'response' => $response->body(),
            ]);
        }
    }

    /**
     * Send notification via Web Push API
     */
    private function sendWebPush(
        UserPushSubscription $subscription,
        string $title,
        string $body,
        array $data = []
    ): void {
        $payload = [
            'title' => $title,
            'body' => $body,
            'icon' => url('/icon-192.png'),
            'badge' => url('/badge-72.png'),
            'data' => array_merge($data, [
                'url' => url('/orders/' . ($data['order_id'] ?? '')),
            ]),
        ];

        // For Web Push, we need to use a library like minishlink/web-push
        // This is a simplified implementation using HTTP
        // In production, use the proper Web Push library
        
        Log::info('Web Push notification prepared', [
            'endpoint' => $subscription->endpoint,
            'payload' => $payload,
        ]);

        // Note: Actual Web Push implementation requires VAPID keys and proper encryption
        // This is a placeholder for the implementation
    }

    /**
     * Subscribe user to push notifications
     */
    public function subscribe(
        int $userId,
        string $deviceType,
        string $endpoint,
        array $keys = null
    ): UserPushSubscription {
        return UserPushSubscription::updateOrCreate(
            [
                'user_id' => $userId,
                'endpoint' => $endpoint,
            ],
            [
                'device_type' => $deviceType,
                'keys' => $keys,
                'is_active' => true,
            ]
        );
    }

    /**
     * Unsubscribe user from push notifications
     */
    public function unsubscribe(int $userId, string $endpoint): bool
    {
        $subscription = UserPushSubscription::where('user_id', $userId)
            ->where('endpoint', $endpoint)
            ->first();

        if (!$subscription) {
            return false;
        }

        $subscription->deactivate();
        return true;
    }

    /**
     * Unsubscribe all devices for user
     */
    public function unsubscribeAll(int $userId): int
    {
        return UserPushSubscription::where('user_id', $userId)
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
