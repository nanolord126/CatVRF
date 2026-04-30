<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Models\UserWhatsAppLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class WhatsAppService
{
    private string $baseUrl;
    private string $token;
    private string $instanceId;
    private string $provider;

    public function __construct()
    {
        $this->provider = config('services.whatsapp.provider', 'ultramsg');
        $this->baseUrl = config('services.whatsapp.base_url', '');
        $this->token = config('services.whatsapp.token', '');
        $this->instanceId = config('services.whatsapp.instance_id', '');
    }

    /**
     * Send order notification to buyer/seller
     */
    public function sendOrderNotification(
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        $links = UserWhatsAppLink::whereIn('user_id', [$buyerId, $sellerId])
            ->active()
            ->verified()
            ->get();

        foreach ($links as $link) {
            $recipientType = $link->user_id === $buyerId ? 'buyer' : 'seller';
            $message = $this->buildOrderMessage($orderId, $eventType, $recipientType, $data);

            $this->sendTextMessage($link->phone, $message);
            $link->markAsNotified();
        }
    }

    /**
     * Send notification to specific user
     */
    public function sendNotificationToUser(int $userId, string $message): bool
    {
        $link = UserWhatsAppLink::where('user_id', $userId)
            ->active()
            ->verified()
            ->first();

        if (!$link) {
            Log::info('User has no active WhatsApp link', ['user_id' => $userId]);
            return false;
        }

        $result = $this->sendTextMessage($link->phone, $message);

        if ($result) {
            $link->markAsNotified();
        }

        return $result;
    }

    /**
     * Send text message via WhatsApp
     */
    public function sendTextMessage(string $phone, string $message): bool
    {
        try {
            return match($this->provider) {
                'ultramsg' => $this->sendViaUltraMsg($phone, $message),
                'twilio' => $this->sendViaTwilio($phone, $message),
                'official' => $this->sendViaOfficialAPI($phone, $message),
                default => $this->sendViaUltraMsg($phone, $message),
            };
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendTextMessage failed', [
                'phone' => $phone,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send via UltraMsg provider
     */
    private function sendViaUltraMsg(string $phone, string $message): bool
    {
        $response = Http::post("{$this->baseUrl}/messages/chat", [
            'token' => $this->token,
            'to' => $phone,
            'body' => $message,
        ]);

        if (!$response->successful()) {
            Log::error('UltraMsg API error', [
                'response' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Send via Twilio provider
     */
    private function sendViaTwilio(string $phone, string $message): bool
    {
        $response = Http::asForm()->post(
            "https://api.twilio.com/2010-04-01/Accounts/{$this->instanceId}/Messages.json",
            [
                'From' => 'whatsapp:' . config('services.whatsapp.from_number'),
                'To' => 'whatsapp:' . $phone,
                'Body' => $message,
            ],
            [
                'auth' => [$this->instanceId, $this->token],
            ]
        );

        return $response->successful();
    }

    /**
     * Send via WhatsApp Official API (Meta)
     */
    private function sendViaOfficialAPI(string $phone, string $message): bool
    {
        $response = Http::post("{$this->baseUrl}/{$this->instanceId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ], [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
            ],
        ]);

        return $response->successful();
    }

    /**
     * Send interactive message with buttons
     */
    public function sendInteractiveMessage(
        string $phone,
        string $headerText,
        string $bodyText,
        array $buttons
    ): bool {
        try {
            return match($this->provider) {
                'ultramsg' => $this->sendInteractiveViaUltraMsg($phone, $headerText, $bodyText, $buttons),
                'official' => $this->sendInteractiveViaOfficialAPI($phone, $headerText, $bodyText, $buttons),
                default => $this->sendTextMessage($phone, $bodyText),
            };
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendInteractiveMessage failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function sendInteractiveViaUltraMsg(
        string $phone,
        string $headerText,
        string $bodyText,
        array $buttons
    ): bool {
        // UltraMsg doesn't support interactive messages natively, fallback to text
        return $this->sendTextMessage($phone, $bodyText);
    }

    private function sendInteractiveViaOfficialAPI(
        string $phone,
        string $headerText,
        string $bodyText,
        array $buttons
    ): bool {
        $buttonObjects = [];
        foreach ($buttons as $button) {
            $buttonObjects[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $button['id'],
                    'title' => $button['title'],
                ],
            ];
        }

        $response = Http::post("{$this->baseUrl}/{$this->instanceId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'header' => [
                    'type' => 'text',
                    'text' => $headerText,
                ],
                'body' => [
                    'text' => $bodyText,
                ],
                'action' => [
                    'buttons' => $buttonObjects,
                ],
            ],
        ], [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
            ],
        ]);

        return $response->successful();
    }

    /**
     * Send template message (pre-approved by WhatsApp)
     */
    public function sendTemplateMessage(string $phone, string $templateName, array $parameters = []): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/{$this->instanceId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => 'ru',
                    ],
                ],
            ], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendTemplateMessage failed', [
                'phone' => $phone,
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate verification code for phone linking
     */
    public function generateVerificationLink(int $userId): string
    {
        $link = UserWhatsAppLink::firstOrCreate(
            ['user_id' => $userId],
            ['phone' => '', 'is_active' => false]
        );

        $code = $link->generateVerificationCode();
        return $code;
    }

    /**
     * Verify phone number with code
     */
    public function verifyPhone(int $userId, string $phone, string $code): ?UserWhatsAppLink
    {
        $link = UserWhatsAppLink::where('user_id', $userId)
            ->where('verification_code', $code)
            ->first();

        if (!$link) {
            return null;
        }

        $link->update([
            'phone' => $phone,
            'is_active' => true,
        ]);
        $link->verify();

        return $link;
    }

    /**
     * Unlink WhatsApp account
     */
    public function unlinkAccount(int $userId): bool
    {
        $link = UserWhatsAppLink::where('user_id', $userId)->first();

        if (!$link) {
            return false;
        }

        $link->update(['is_active' => false]);
        return true;
    }

    private function buildOrderMessage(
        int $orderId,
        string $eventType,
        string $recipientType,
        array $data
    ): string {
        $trackingUrl = "https://catvrf.ru/orders/{$orderId}";

        if ($eventType === 'created') {
            if ($recipientType === 'buyer') {
                return "✅ *Ваш заказ оформлен!*\n\n" .
                       "Номер: *{$orderId}*\n" .
                       "Сумма: *" . ($data['amount'] ?? '0') . " ₽*\n\n" .
                       "Отслеживать: {$trackingUrl}";
            }
            return "🛒 *Новый заказ в супермаркете!*\n\n" .
                   "Номер: *{$orderId}*\n" .
                   "Сумма: *" . ($data['amount'] ?? '0') . " ₽*\n" .
                   "Проверьте панель управления.";
        }

        return match($eventType) {
            'confirmed' => "✅ *Заказ подтверждён!*\n\n" .
                           "Номер: *{$orderId}*\n" .
                           "Товары зарезервированы.",

            'ready_for_delivery' => "📦 *Заказ готов к выдаче!*\n\n" .
                                   "Номер: *{$orderId}*\n\n" .
                                   "Пожалуйста, подтвердите в панели.",

            'in_delivery' => "🚚 *Курьер в пути!*\n" .
                            "Заказ №{$orderId}\n" .
                            "Примерное время: " . ($data['eta'] ?? '30') . " мин",

            'delivered' => "🎉 *Заказ доставлен!*\n\n" .
                          "Номер: {$orderId}\n" .
                          "Оцените покупку в приложении!",

            'cancelled' => "❌ *Заказ отменён*\n\n" .
                          "Номер: {$orderId}\n" .
                          "Причина: " . ($data['reason'] ?? 'Не указана'),

            default => "Обновление по заказу №{$orderId}: {$eventType}"
        };
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
                    [
                        'id' => "track_{$orderId}",
                        'title' => '📍 Отследить',
                    ],
                    [
                        'id' => "my_orders_{$orderId}",
                        'title' => '🛍 Мои заказы',
                    ],
                ],
            ] : [
                'header' => 'Новый заказ',
                'body' => "🛒 Новый заказ №{$orderId}\nСумма: " . ($data['amount'] ?? '0') . " ₽",
                'buttons' => [
                    [
                        'id' => "accept_{$orderId}",
                        'title' => '✅ Принять',
                    ],
                    [
                        'id' => "reject_{$orderId}",
                        'title' => '❌ Отклонить',
                    ],
                ],
            ],

            'ready_for_delivery' => [
                'header' => 'Заказ готов',
                'body' => "📦 Заказ №{$orderId} готов к доставке!",
                'buttons' => [
                    [
                        'id' => "start_delivery_{$orderId}",
                        'title' => '🚚 Передать курьеру',
                    ],
                ],
            ],

            'in_delivery' => [
                'header' => 'Курьер в пути',
                'body' => "🚚 Курьер в пути!\nЗаказ №{$orderId}\nПримерное время: " . ($data['eta'] ?? '30') . " мин",
                'buttons' => [
                    [
                        'id' => "track_{$orderId}",
                        'title' => '📍 На карте',
                    ],
                ],
            ],

            default => [
                'header' => 'Статус заказа',
                'body' => "Заказ №{$orderId}: {$eventType}",
                'buttons' => [
                    [
                        'id' => "track_{$orderId}",
                        'title' => '📍 Отследить',
                    ],
                ],
            ],
        };
    }
}
