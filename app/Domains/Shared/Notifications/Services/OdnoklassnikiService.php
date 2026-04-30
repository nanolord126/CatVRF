<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\UserOdnoklassnikiLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class OdnoklassnikiService
{
    private string $applicationKey;
    private string $applicationSecret;
    private string $accessToken;
    private string $apiUrl;

    public function __construct()
    {
        $this->applicationKey = config('services.odnoklassniki.application_key', '');
        $this->applicationSecret = config('services.odnoklassniki.application_secret', '');
        $this->accessToken = config('services.odnoklassniki.access_token', '');
        $this->apiUrl = config('services.odnoklassniki.api_url', 'https://api.ok.ru');
    }

    /**
     * Send order notification via Odnoklassniki
     */
    public function sendOrderNotification(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UserOdnoklassnikiLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $recipientType = $link->user_id === $buyerId ? 'buyer' : 'seller';
            $message = $this->buildOrderMessage($orderId, $eventType, $recipientType, $data);

            try {
                $this->sendMessage($link->ok_user_id, $message);
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Failed to send Odnoklassniki notification', [
                    'ok_user_id' => $link->ok_user_id,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send message via Odnoklassniki API
     */
    public function sendMessage(string $okUserId, string $message): bool
    {
        $response = Http::post("{$this->apiUrl}/fb.do", [
            'method' => 'messages.send',
            'access_token' => $this->accessToken,
            'application_key' => $this->applicationKey,
            'recipient' => [
                'user_id' => (string) $okUserId,
            ],
            'message' => [
                'text' => $message,
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Odnoklassniki API error', [
                'response' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    public function sendNotificationToUser(int $userId, string $message): bool
    {
        $link = UserOdnoklassnikiLink::where('user_id', $userId)
            ->active()
            ->verified()
            ->first();

        if (!$link) {
            return false;
        }

        $result = $this->sendMessage($link->ok_user_id, $message);

        if ($result) {
            $link->markAsNotified();
        }

        return $result;
    }

    public function linkAccount(int $userId, string $okUserId, string $okUsername = null): ?UserOdnoklassnikiLink
    {
        $link = UserOdnoklassnikiLink::firstOrCreate(
            ['user_id' => $userId],
            ['ok_user_id' => $okUserId, 'ok_username' => $okUsername, 'is_active' => false]
        );

        $link->verify();
        return $link;
    }

    public function unlinkAccount(int $userId): bool
    {
        $link = UserOdnoklassnikiLink::where('user_id', $userId)->first();

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
    private function sendInteractiveMessage(string $okUserId, string $header, string $body, array $buttons): bool
    {
        $response = Http::post("{$this->apiUrl}/fb.do", [
            'method' => 'messages.send',
            'access_token' => $this->accessToken,
            'application_key' => $this->applicationKey,
            'recipient' => [
                'user_id' => (string) $okUserId,
            ],
            'message' => [
                'text' => $body,
                'attachments' => array_map(fn($btn) => [
                    'type' => 'button',
                    'text' => $btn['title'],
                    'payload' => $btn['id'],
                ], $buttons),
            ],
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
        $links = UserOdnoklassnikiLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $isBuyer = $link->user_id === $buyerId;
            $payload = $this->buildInteractivePayload($orderId, $eventType, $isBuyer, $data);

            try {
                $this->sendInteractiveMessage(
                    $link->ok_user_id,
                    $payload['header'] ?? '',
                    $payload['body'],
                    $payload['buttons']
                );
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Odnoklassniki interactive message failed, falling back to text', [
                    'ok_user_id' => $link->ok_user_id,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
                $message = $this->buildOrderMessage($orderId, $eventType, $isBuyer ? 'buyer' : 'seller', $data);
                $this->sendMessage($link->ok_user_id, $message);
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
