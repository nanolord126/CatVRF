<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\UserSignalLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class SignalService
{
    private string $signalApiUrl;
    private string $signalNumber;

    public function __construct()
    {
        $this->signalApiUrl = config('services.signal.api_url', '');
        $this->signalNumber = config('services.signal.number', '');
    }

    /**
     * Send order notification via Signal
     * Note: Signal doesn't have an official API, this uses third-party services
     */
    public function sendOrderNotification(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UserSignalLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $recipientType = $link->user_id === $buyerId ? 'buyer' : 'seller';
            $message = $this->buildOrderMessage($orderId, $eventType, $recipientType, $data);

            try {
                $this->sendMessage($link->signal_id, $message);
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Failed to send Signal notification', [
                    'signal_id' => $link->signal_id,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send message via third-party Signal service
     */
    public function sendMessage(string $signalId, string $message): bool
    {
        // Note: Signal doesn't have an official API
        // This implementation uses third-party services like signal-cli or REST APIs
        // For production, consider using signal-cli with REST API
        
        $response = Http::post($this->signalApiUrl, [
            'number' => $signalId,
            'message' => $message,
        ]);

        if (!$response->successful()) {
            Log::error('Signal API error', [
                'response' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    public function sendNotificationToUser(int $userId, string $message): bool
    {
        $link = UserSignalLink::where('user_id', $userId)
            ->active()
            ->verified()
            ->first();

        if (!$link) {
            return false;
        }

        $result = $this->sendMessage($link->signal_id, $message);

        if ($result) {
            $link->markAsNotified();
        }

        return $result;
    }

    public function linkAccount(int $userId, string $signalId): ?UserSignalLink
    {
        $link = UserSignalLink::firstOrCreate(
            ['user_id' => $userId],
            ['signal_id' => $signalId, 'is_active' => false]
        );

        $link->verify();
        return $link;
    }

    public function unlinkAccount(int $userId): bool
    {
        $link = UserSignalLink::where('user_id', $userId)->first();

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
    private function sendInteractiveMessage(string $signalId, string $header, string $body, array $buttons): bool
    {
        // Signal doesn't have native interactive messages, append buttons as options
        $message = $body . "\n\n" . implode("\n", array_map(fn($btn) => "[{$btn['title']}](id:{$btn['id']})", $buttons));

        return $this->sendMessage($signalId, $message);
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
        $links = UserSignalLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $isBuyer = $link->user_id === $buyerId;
            $payload = $this->buildInteractivePayload($orderId, $eventType, $isBuyer, $data);

            try {
                $this->sendInteractiveMessage(
                    $link->signal_id,
                    $payload['header'] ?? '',
                    $payload['body'],
                    $payload['buttons']
                );
                $link->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Signal interactive message failed, falling back to text', [
                    'signal_id' => $link->signal_id,
                    'order_id' => $orderId,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                ]);
                $message = $this->buildOrderMessage($orderId, $eventType, $isBuyer ? 'buyer' : 'seller', $data);
                $this->sendMessage($link->signal_id, $message);
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
