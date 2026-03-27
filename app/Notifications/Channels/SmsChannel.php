<?php declare(strict_types=1);

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Services\SmsService;

/**
 * SMS Notification Channel - отправляет уведомления через SMS
 * 
 * Поддерживает:
 * - Twilio
 * - Vonage (Nexmo)
 * - Other SMS providers
 */
class SmsChannel
{
    /**
     * Инстанс SmsService
     */
    protected SmsService $smsService;

    /**
     * Конструктор
     */
    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Отправить уведомление через SMS
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Проверить, что объект имеет метод toSms
        if (!method_exists($notification, 'toSms')) {
            Log::warning('Notification does not have toSms method', [
                'notification_class' => get_class($notification),
                'notifiable_id' => $notifiable->id,
            ]);
            return;
        }

        try {
            // Получить номер телефона
            $phone = $this->getPhoneNumber($notifiable);
            if (!$phone) {
                throw new \Exception("No phone number found for notifiable: {$notifiable->id}");
            }

            // Получить данные SMS
            $smsData = $notification->toSms();
            $smsData['to'] = $phone;

            // Отправить через SmsService
            $this->smsService->send(
                to: $phone,
                message: $smsData['message'] ?? '',
                correlationId: $notification->getCorrelationId(),
                tenantId: $notification->getTenantId(),
                priority: $smsData['priority'] ?? 'normal',
            );

            Log::channel('audit')->info('SMS notification sent', [
                'type' => $notification->getType(),
                'phone' => $this->maskPhone($phone),
                'correlation_id' => $notification->getCorrelationId(),
                'tenant_id' => $notification->getTenantId(),
            ]);

        } catch (\Exception $e) {
            Log::channel('notifications')->error('Failed to send SMS notification', [
                'notification_class' => get_class($notification),
                'notifiable_id' => $notifiable->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить номер телефона уведомляемого объекта
     */
    protected function getPhoneNumber(object $notifiable): ?string
    {
        // Проверить разные методы получения номера
        if (isset($notifiable->phone)) {
            return $notifiable->phone;
        }

        if (isset($notifiable->phone_number)) {
            return $notifiable->phone_number;
        }

        if (method_exists($notifiable, 'getPhoneForNotifications')) {
            return $notifiable->getPhoneForNotifications();
        }

        if (method_exists($notifiable, 'routeNotificationForSms')) {
            return $notifiable->routeNotificationForSms();
        }

        return null;
    }

    /**
     * Замаскировать номер для логирования
     */
    protected function maskPhone(string $phone): string
    {
        return substr($phone, 0, -4) . '****';
    }
}
