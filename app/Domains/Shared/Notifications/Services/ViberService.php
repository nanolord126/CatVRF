<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\UserViberLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class ViberService
{
    private string $apiToken;
    private string $senderName;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiToken = config('services.viber.api_token', '');
        $this->senderName = config('services.viber.sender_name', 'CatVRF');
        $this->apiUrl = config('services.viber.api_url', 'https://chatapi.viber.com/pa');
    }

    /**
     * Send order notification via Viber
     */
    public function sendOrderNotification(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UserViberLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $recipientType = $link->user_id === $buyerId ? 'buyer' : 'seller';
            $message = $this->buildOrderMessage($orderId, $eventType, $recipientType, $data);

            try {
                $this->sendMessage($link->viber_id, $message);
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Failed to send Viber notification', [
                    'viber_id' => $link->viber_id,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send message to Viber user
     */
    public function sendMessage(string $viberId, string $message): bool
    {
        $response = Http::post("{$this->apiUrl}/send_message", [
            'receiver' => $viberId,
            'min_api_version' => 7,
            'sender' => [
                'name' => $this->senderName,
            ],
            'type' => 'text',
            'text' => $message,
        ], [
            'headers' => [
                'X-Viber-Auth-Token' => $this->apiToken,
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Viber API error', [
                'response' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Send message with buttons
     */
    public function sendMessageWithButtons(
        string $viberId,
        string $message,
        array $buttons
    ): bool {
        $response = Http::post("{$this->apiUrl}/send_message", [
            'receiver' => $viberId,
            'min_api_version' => 7,
            'sender' => [
                'name' => $this->senderName,
            ],
            'type' => 'text',
            'text' => $message,
            'keyboard' => [
                'Type' => 'keyboard',
                'Buttons' => $buttons,
            ],
        ], [
            'headers' => [
                'X-Viber-Auth-Token' => $this->apiToken,
            ],
        ]);

        return $response->successful();
    }

    /**
     * Send notification to specific user
     */
    public function sendNotificationToUser(int $userId, string $message): bool
    {
        $link = UserViberLink::where('user_id', $userId)
            ->active()
            ->verified()
            ->first();

        if (!$link) {
            return false;
        }

        $result = $this->sendMessage($link->viber_id, $message);

        if ($result) {
            $link->markAsNotified();
        }

        return $result;
    }

    /**
     * Link account via verification token
     */
    public function linkAccount(int $userId, string $viberId): ?UserViberLink
    {
        $link = UserViberLink::firstOrCreate(
            ['user_id' => $userId],
            ['viber_id' => $viberId, 'is_active' => false]
        );

        $link->verify();
        return $link;
    }

    /**
     * Unlink account
     */
    public function unlinkAccount(int $userId): bool
    {
        $link = UserViberLink::where('user_id', $userId)->first();

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
                    ['ActionBody' => "track_{$orderId}", 'Text' => '📍 Отследить'],
                    ['ActionBody' => "my_orders_{$orderId}", 'Text' => '🛍 Мои заказы'],
                ],
            ] : [
                'header' => 'Новый заказ',
                'body' => "🛒 Новый заказ №{$orderId}\nСумма: " . ($data['amount'] ?? '0') . " ₽",
                'buttons' => [
                    ['ActionBody' => "accept_{$orderId}", 'Text' => '✅ Принять'],
                    ['ActionBody' => "reject_{$orderId}", 'Text' => '❌ Отклонить'],
                ],
            ],

            'ready_for_delivery' => [
                'header' => 'Заказ готов',
                'body' => "📦 Заказ №{$orderId} готов к доставке!",
                'buttons' => [
                    ['ActionBody' => "start_delivery_{$orderId}", 'Text' => '🚚 Передать курьеру'],
                ],
            ],

            'in_delivery' => [
                'header' => 'Курьер в пути',
                'body' => "🚚 Курьер в пути!\nЗаказ №{$orderId}",
                'buttons' => [
                    ['ActionBody' => "track_{$orderId}", 'Text' => '📍 На карте'],
                ],
            ],

            default => [
                'header' => 'Статус заказа',
                'body' => "Заказ №{$orderId}: {$eventType}",
                'buttons' => [
                    ['ActionBody' => "track_{$orderId}", 'Text' => '📍 Отследить'],
                ],
            ],
        };
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
        $links = UserViberLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $isBuyer = $link->user_id === $buyerId;
            $payload = $this->buildInteractivePayload($orderId, $eventType, $isBuyer, $data);

            try {
                $this->sendMessageWithButtons(
                    $link->viber_id,
                    $payload['header'] . "\n\n" . $payload['body'],
                    array_map(fn($btn) => [
                        'ActionType' => 'reply',
                        'ActionBody' => $btn['ActionBody'],
                        'Text' => $btn['Text'],
                        'TextSize' => 'regular',
                        'BgColor' => '#2db9cb',
                    ], $payload['buttons'])
                );
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Viber interactive message failed, falling back to text', [
                    'viber_id' => $link->viber_id,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
                $message = $this->buildOrderMessage($orderId, $eventType, $isBuyer ? 'buyer' : 'seller', $data);
                $this->sendMessage($link->viber_id, $message);
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
