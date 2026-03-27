<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

/**
 * SMS Service - отправляет SMS через Twilio/Vonage
 */
class SmsService
{
    /**
     * Twilio клиент
     */
    protected TwilioClient $twilio;

    /**
     * Vonage API (для альтернативы)
     */
    protected ?string $vonageApiKey;

    /**
     * Конструктор
     */
    public function __construct()
    {
        // Инициализировать Twilio если конфиг есть
        if (config('services.twilio.auth_token')) {
            $this->twilio = new TwilioClient(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token')
            );
        }

        $this->vonageApiKey = config('services.vonage.api_key');
    }

    /**
     * Отправить SMS через Twilio
     */
    public function send(
        string $to,
        string $message,
        ?string $correlationId = null,
        ?int $tenantId = null,
        string $priority = 'normal'
    ): bool {
        try {
            if (!isset($this->twilio)) {
                throw new \Exception('Twilio not configured');
            }

            // Нормализовать номер
            $to = $this->normalizePhoneNumber($to);

            // Отправить через Twilio
            $this->twilio->messages->create(
                $to,
                [
                    'from' => config('services.twilio.phone_number'),
                    'body' => $message,
                ]
            );

            Log::channel('audit')->info('SMS sent', [
                'to' => $this->maskPhone($to),
                'correlation_id' => $correlationId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'to' => isset($to) ? $this->maskPhone($to) : 'unknown',
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
        if (method_exists($notification, 'toSms') && $user->phone) {
            $data = $notification->toSms();
            $this->send(
                to: $user->phone,
                message: $data['message'] ?? '',
                correlationId: $correlationId,
                tenantId: $user->tenant_id ?? null,
            );
        }
    }

    /**
     * Нормализовать номер телефона
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Удалить всё кроме цифр и +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Добавить + если нет
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Замаскировать номер для логирования
     */
    protected function maskPhone(string $phone): string
    {
        return substr($phone, 0, -4) . '****';
    }
}
