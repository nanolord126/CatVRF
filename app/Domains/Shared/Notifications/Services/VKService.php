<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\UserVKLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class VKService
{
    private string $apiVersion;
    private string $accessToken;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiVersion = config('services.vk.api_version', '5.131');
        $this->accessToken = config('services.vk.access_token', '');
        $this->apiUrl = config('services.vk.api_url', 'https://api.vk.com/method');
    }

    /**
     * Send order notification via VK
     */
    public function sendOrderNotification(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UserVKLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $recipientType = $link->user_id === $buyerId ? 'buyer' : 'seller';
            $message = $this->buildOrderMessage($orderId, $eventType, $recipientType, $data);

            try {
                $this->sendMessage($link->vk_user_id, $message);
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Failed to send VK notification', [
                    'vk_user_id' => $link->vk_user_id,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send message via VK Messages API
     */
    public function sendMessage(string $vkUserId, string $message): bool
    {
        $response = Http::get("{$this->apiUrl}/messages.send", [
            'user_id' => $vkUserId,
            'message' => $message,
            'access_token' => $this->accessToken,
            'v' => $this->apiVersion,
            'random_id' => random_int(0, 1000000),
        ]);

        if (!$response->successful()) {
            Log::error('VK API error', [
                'response' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    public function sendNotificationToUser(int $userId, string $message): bool
    {
        $link = UserVKLink::where('user_id', $userId)
            ->active()
            ->verified()
            ->first();

        if (!$link) {
            return false;
        }

        $result = $this->sendMessage($link->vk_user_id, $message);

        if ($result) {
            $link->markAsNotified();
        }

        return $result;
    }

    public function linkAccount(int $userId, string $vkUserId, string $vkUsername = null): ?UserVKLink
    {
        $link = UserVKLink::firstOrCreate(
            ['user_id' => $userId],
            ['vk_user_id' => $vkUserId, 'vk_username' => $vkUsername, 'is_active' => false]
        );

        $link->verify();
        return $link;
    }

    public function unlinkAccount(int $userId): bool
    {
        $link = UserVKLink::where('user_id', $userId)->first();

        if (!$link) {
            return false;
        }

        $link->unlink();
        return true;
    }

    /**
     * Build interactive message payload for order notifications
     */
    private function buildInteractivePayload(
        int $orderId,
        string $eventType,
        bool $isBuyer,
        array $data
    ): array {
        $trackingUrl = "https://catvrf.ru/orders/{$orderId}";

        return match($eventType) {
            'created' => $isBuyer ? [
                'header' => 'Заказ оформлен',
                'body' => "✅ Ваш заказ №{$orderId} оформлен!\nСумма: " . ($data['amount'] ?? '0') . " ₽",
                'buttons' => [
                    ['id' => "track_{$orderId}", 'title' => '📍 Отследить'],
                    ['id' => "my_orders_{$orderId}", 'title' => '🛍 Мои заказы'],
                ],
            ] : [
                'header' => 'Новый заказ',
                'body' => "🛒 Новый заказ №{$orderId}\nСумма: " . ($data['amount'] ?? '0') . " ₽",
                'buttons' => [
                    ['id' => "accept_{$orderId}", 'title' => '✅ Принять'],
                    ['id' => "reject_{$orderId}", 'title' => '❌ Отклонить'],
                ],
            ],

            'ready_for_delivery' => [
                'header' => 'Заказ готов',
                'body' => "📦 Заказ №{$orderId} готов к доставке!",
                'buttons' => [
                    ['id' => "start_delivery_{$orderId}", 'title' => '🚚 Передать курьеру'],
                ],
            ],

            'in_delivery' => [
                'header' => 'Курьер в пути',
                'body' => "🚚 Курьер в пути!\nЗаказ №{$orderId}",
                'buttons' => [
                    ['id' => "track_{$orderId}", 'title' => '📍 На карте'],
                ],
            ],

            default => [
                'header' => 'Статус заказа',
                'body' => "Заказ №{$orderId}: {$eventType}",
                'buttons' => [
                    ['id' => "track_{$orderId}", 'title' => '📍 Отследить'],
                ],
            ],
        };
    }

    /**
     * Send interactive message with buttons
     */
    private function sendInteractiveMessage(string $vkUserId, string $header, string $body, array $buttons): bool
    {
        $keyboard = [
            'one_time' => false,
            'buttons' => array_map(fn($btn) => [[
                'action' => [
                    'type' => 'text',
                    'payload' => json_encode(['button_id' => $btn['id']]),
                    'label' => $btn['title'],
                ],
                'color' => 'primary',
            ]], $buttons),
        ];

        $response = Http::get("{$this->apiUrl}/messages.send", [
            'user_id' => $vkUserId,
            'message' => $body,
            'access_token' => $this->accessToken,
            'v' => $this->apiVersion,
            'random_id' => random_int(0, 1000000),
            'keyboard' => json_encode($keyboard),
        ]);

        return $response->successful();
    }

    /**
     * Send interactive order notification with buttons
     */
    public function sendInteractive(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UserVKLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $isBuyer = $link->user_id === $buyerId;
            $payload = $this->buildInteractivePayload($orderId, $eventType, $isBuyer, $data);

            try {
                $this->sendInteractiveMessage(
                    $link->vk_user_id,
                    $payload['header'] ?? '',
                    $payload['body'],
                    $payload['buttons']
                );
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('VK interactive message failed, falling back to text', [
                    'vk_user_id' => $link->vk_user_id,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
                $message = $this->buildOrderMessage($orderId, $eventType, $isBuyer ? 'buyer' : 'seller', $data);
                $this->sendMessage($link->vk_user_id, $message);
                $link->markAsNotified();
            }
        }
    }

    private function buildOrderMessage(
        int $orderId,
        string $eventType,
        string $recipientType,
        array $data
    ): string {
        $trackingUrl = url("/orders/{$orderId}");

        if ($eventType === 'created') {
            if ($recipientType === 'buyer') {
                return "✅ Ваш заказ оформлен!\n\n" .
                       "Номер: {$orderId}\n" .
                       "Сумма: " . ($data['amount'] ?? '0') . " ₽\n\n" .
                       "Отслеживать: {$trackingUrl}";
            }
            return "🛒 Новый заказ в супермаркете!\n\n" .
                   "Номер: {$orderId}\n" .
                   "Сумма: " . ($data['amount'] ?? '0') . " ₽";
        }

        return match($eventType) {
            'confirmed' => "✅ Заказ №{$orderId} подтверждён\nТовары зарезервированы.",
            'ready_for_delivery' => "📦 Заказ №{$orderId} готов к выдаче\nПожалуйста, подтвердите в панели.",
            'in_delivery' => "🚚 Курьер в пути!\nЗаказ №{$orderId}\nПримерное время: " . ($data['eta'] ?? '30') . " мин",
            'delivered' => "🎉 Заказ №{$orderId} доставлен\nОцените покупку!",
            'cancelled' => "❌ Заказ №{$orderId} отменён\nПричина: " . ($data['reason'] ?? 'Не указана'),
            default => "Обновление заказа №{$orderId}: {$eventType}"
        };
    }
}
