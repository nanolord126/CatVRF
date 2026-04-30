<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\UserTelegramLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class TelegramBotService
{
    private const TELEGRAM_API_URL = 'https://api.telegram.org/bot';

    public function __construct(
        private ?string $botToken = null
    ) {
        $this->botToken = $botToken ?? config('services.telegram.bot_token', '');
    }

    public function getBotToken(): string
    {
        return $this->botToken;
    }

    public function sendMessage(int $chatId, string $message, array $keyboard = null): bool
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ];

            if ($keyboard) {
                $payload['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboard
                ]);
            }

            $response = Http::post(
                self::TELEGRAM_API_URL . $this->botToken . '/sendMessage',
                $payload
            );

            if (!$response->successful()) {
                Log::error('Telegram API error', [
                    'chat_id' => $chatId,
                    'response' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send Telegram message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendToUser(int $userId, string $message, array $keyboard = null): bool
    {
        $link = UserTelegramLink::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$link) {
            Log::info('User has no active Telegram link', ['user_id' => $userId]);
            return false;
        }

        $result = $this->sendMessage($link->telegram_id, $message, $keyboard);

        if ($result) {
            $link->update(['last_notified_at' => now()]);
        }

        return $result;
    }

    public function getWebhookUrl(): string
    {
        return self::TELEGRAM_API_URL . $this->botToken . '/setWebhook';
    }

    public function setWebhook(string $url): bool
    {
        try {
            $response = Http::post($this->getWebhookUrl(), [
                'url' => $url,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Failed to set Telegram webhook', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getWebhookInfo(): ?array
    {
        try {
            $response = Http::get(self::TELEGRAM_API_URL . $this->botToken . '/getWebhookInfo');

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Failed to get Telegram webhook info', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function deleteWebhook(): bool
    {
        try {
            $response = Http::post(self::TELEGRAM_API_URL . $this->botToken . '/deleteWebhook');

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Failed to delete Telegram webhook', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendOrderNotification(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UserTelegramLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->get();

        foreach ($links as $link) {
            $recipientType = $link->user_id === $buyerId ? 'buyer' : 'seller';
            $message = $this->buildOrderMessage($orderId, $eventType, $recipientType, $data);
            $keyboard = $this->buildInlineKeyboard($orderId, $eventType, $recipientType);

            $this->sendMessage($link->telegram_id, $message, $keyboard);
            $link->update(['last_notified_at' => now()]);
        }
    }

    public function sendPhoto(int $chatId, string $photoUrl, string $caption = ''): bool
    {
        try {
            $response = Http::post(self::TELEGRAM_API_URL . $this->botToken . '/sendPhoto', [
                'chat_id' => $chatId,
                'photo' => $photoUrl,
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram sendPhoto failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function generateVerificationLink(int $userId): string
    {
        $link = UserTelegramLink::firstOrCreate(
            ['user_id' => $userId],
            ['telegram_id' => 0, 'is_active' => false]
        );

        $token = bin2hex(random_bytes(16));
        $link->update(['verification_token' => $token]);

        $botUsername = config('services.telegram.bot_username');
        return "https://t.me/{$botUsername}?start={$token}";
    }

    public function linkAccount(int $telegramId, string $token): ?UserTelegramLink
    {
        $link = UserTelegramLink::where('verification_token', $token)->first();

        if (!$link) {
            return null;
        }

        $link->update([
            'telegram_id' => $telegramId,
            'is_active' => true,
            'verified_at' => now(),
        ]);

        return $link;
    }

    public function unlinkAccount(int $userId): bool
    {
        $link = UserTelegramLink::where('user_id', $userId)->first();

        if (!$link) {
            return false;
        }

        $link->update(['is_active' => false]);
        return true;
    }

    /**
     * Send interactive order notification with inline keyboard
     */
    public function sendInteractive(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UserTelegramLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->get();

        foreach ($links as $link) {
            $isBuyer = $link->user_id === $buyerId;
            $message = $this->buildOrderMessage($orderId, $eventType, $isBuyer ? 'buyer' : 'seller', $data);
            $keyboard = $this->buildInlineKeyboard($orderId, $eventType, $isBuyer ? 'buyer' : 'seller');

            $this->sendMessage($link->telegram_id, $message, $keyboard);
            $link->update(['last_notified_at' => now()]);
        }
    }

    private function buildInlineKeyboard(int $orderId, string $eventType, string $recipientType): ?array
    {
        $keyboard = [];

        return match($eventType) {
            'created' => $recipientType === 'buyer'
                ? [
                    [
                        ['text' => '📍 Отследить заказ', 'callback_data' => "track_order_{$orderId}"],
                        ['text' => '🛍 Мои заказы', 'callback_data' => 'my_orders'],
                    ]
                ]
                : [
                    [
                        ['text' => '✅ Принять заказ', 'callback_data' => "accept_order_{$orderId}"],
                        ['text' => '❌ Отклонить', 'callback_data' => "reject_order_{$orderId}"],
                    ]
                ],
ivery' => [
                [
                    ['text' => '🚚 Передать в доставку', 'callback_data' => "start_delivery_{$orderId}"],
                ]
            ],

            'in_delivery' => [
                [
                    ['text' => '📍 Посмотреть на карте', 'callback_data' => "show_map_{$orderId}"],
                    ['text' => 'Проблема с заказом', 'callback_data' => "report_issue_{$orderId}"],
                ]
            ],

            'delivered' => [
                [
                    ['text' => '⭐ Оценить заказ', 'callback_data' => "rate_order_{$orderId}"],
                    ['text' => '📋 Чек', 'callback_data' => "view_receipt_{$orderId}"],
                ]
            ],

            default => null
        };
    }

    private function build
    private function buildOrderMessage(
        int $orderId,
        string $eventType,
        string $recipientType,
        array $data
    ): string {
        if ($eventType === 'created') {
            if ($recipientType === 'buyer') {
                return "<b>✅ Заказ оформлен!</b>\n" .
                       "№{$orderId}\n" .
                       "Сумма: " . ($data['amount'] ?? '0') . " ₽\n" .
                       "Ожидайте подтверждения.";
            }
            return "<b>🛒 Новый заказ!</b>\n" .
                   "№{$orderId}\n" .
                   "Сумма: " . ($data['amount'] ?? '0') . " ₽";
        }

        return match($eventType) {
            'confirmed' => "<b>✅ Подтверждение заказа!</b>\n" .
                           "№{$orderId}\n" .
                           "Продавец принял заказ.",

            'ready_for_delivery' => "<b>📦 Заказ готов к доставке!</b>\n" .
                                   "№{$orderId}\n" .
                                   "Курьер скоро заберет его.",

            'in_delivery' => "<b>🚚 Курьер в пути!</b>\n" .
                            "Заказ №{$orderId}\n" .
                            "ETA: " . ($data['eta'] ?? '30') . " мин",

            'delivered' => "<b>✅ Заказ доставлен!</b>\n" .
                          "№{$orderId}\n" .
                          "Спасибо за покупку!",

            'cancelled' => "<b>❌ Заказ отменен</b>\n" .
                          "№{$orderId}\n" .
                          "Причина: " . ($data['reason'] ?? 'Не указана'),

            default => "Обновление заказа №{$orderId}: {$eventType}"
        };
    }
}
