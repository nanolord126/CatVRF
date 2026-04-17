<?php declare(strict_types=1);

namespace App\Services;



use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Log\LogManager;

/**
 * SMS Service - отправляет SMS через Twilio/Vonage
 */
abstract class SmsService
{
    /**
     * Twilio клиент
     */
    private TwilioClient $twilio;

    /**
     * Vonage API (для альтернативы)
     */
    private readonly ?string $vonageApiKey;

    /**
     * Конструктор
     */
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
    )
    {
        // Инициализировать Twilio если конфиг есть
        if ($this->config->get('services.twilio.auth_token')) {
            $this->twilio = new TwilioClient(
                $this->config->get('services.twilio.account_sid'),
                $this->config->get('services.twilio.auth_token')
            );
        }

        $this->vonageApiKey = $this->config->get('services.vonage.api_key');
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
                throw new \RuntimeException('Twilio not configured');
            }

            // Нормализовать номер
            $to = $this->normalizePhoneNumber($to);

            // Отправить через Twilio
            $this->twilio->messages->create(
                $to,
                [
                    'from' => $this->config->get('services.twilio.phone_number'),
                    'body' => $message,
                ]
            );

            $this->logger->channel('audit')->info('SMS sent', [
                'to' => $this->maskPhone($to),
                'correlation_id' => $correlationId,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);

            $this->logger->error('Failed to send SMS', [
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
