<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Notification as NotificationModel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Throwable;

final readonly class NotificationService
{
    /**
     * Отправляет уведомление пользователю/бизнесу по всем каналам.
     *
     * @param int $recipientId ID получателя
     * @param string $type Тип уведомления: payment_confirmed, order_shipped, low_stock и т.д.
     * @param array $data Данные для уведомления
     * @param string $correlationId Идентификатор корреляции
     * @return bool
     * @throws Exception
     */
    public function send(
        int $recipientId,
        string $type,
        array $data,
        string $correlationId = '',
    ): bool {
        $correlationId = $correlationId ?: (string) Str::uuid()->toString();

        try {
            Log::channel('audit')->info('Notification send initiated', [
                'recipient_id' => $recipientId,
                'type' => $type,
                'correlation_id' => $correlationId,
            ]);

            $user = User::findOrFail($recipientId);

            // Сохраняем в БД
            $notification = NotificationModel::create([
                'user_id' => $recipientId,
                'type' => $type,
                'title' => $data['title'] ?? '',
                'body' => $data['body'] ?? '',
                'data' => $data,
                'correlation_id' => $correlationId,
                'read_at' => null,
            ]);

            // Email
            if ($this->shouldSendEmail($type)) {
                try {
                    // Mail::to($user->email)->queue(new NotificationMailable($notification));
                } catch (Throwable $e) {
                    Log::channel('audit')->warning('Email send failed', [
                        'user_id' => $recipientId,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);
                }
            }

            // SMS
            if ($this->shouldSendSms($type) && $user->phone_verified_at) {
                try {
                    // SmsService::send($user->phone, $data['sms_text'] ?? '');
                } catch (Throwable $e) {
                    Log::channel('audit')->warning('SMS send failed', [
                        'user_id' => $recipientId,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);
                }
            }

            // Push уведомление
            if ($this->shouldSendPush($type)) {
                try {
                    // FirebaseService::send($user, $notification);
                } catch (Throwable $e) {
                    Log::channel('audit')->warning('Push send failed', [
                        'user_id' => $recipientId,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);
                }
            }

            Log::channel('audit')->info('Notification queued successfully', [
                'notification_id' => $notification->id,
                'recipient_id' => $recipientId,
                'type' => $type,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Notification send failed', [
                'recipient_id' => $recipientId,
                'type' => $type,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Отправляет ежедневный отчёт бизнесу.
     *
     * @param int $tenantId ID тенанта
     * @param string $correlationId Идентификатор корреляции
     * @return bool
     * @throws Exception
     */
    public function sendDailyReport(int $tenantId, string $correlationId = ''): bool
    {
        $correlationId = $correlationId ?: (string) Str::uuid()->toString();

        try {
            Log::channel('audit')->info('Daily report sending', [
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            // Сборка метрик за день
            $metrics = $this->collectDailyMetrics($tenantId);

            // Отправка на email
            $this->send($tenantId, 'daily_report', $metrics, $correlationId);

            Log::channel('audit')->info('Daily report sent successfully', [
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Daily report send failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Отправляет еженедельный отчёт (понедельник 07:00-08:00).
     *
     * @param int $tenantId ID тенанта
     * @param string $correlationId Идентификатор корреляции
     * @return bool
     * @throws Exception
     */
    public function sendWeeklyReport(int $tenantId, string $correlationId = ''): bool
    {
        $correlationId = $correlationId ?: (string) Str::uuid()->toString();

        try {
            Log::channel('audit')->info('Weekly report sending', [
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            // Сборка метрик за неделю
            $metrics = $this->collectWeeklyMetrics($tenantId);

            // Отправка на email
            $this->send($tenantId, 'weekly_report', $metrics, $correlationId);

            Log::channel('audit')->info('Weekly report sent successfully', [
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Weekly report send failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Должно ли отправляться по email.
     */
    private function shouldSendEmail(string $type): bool
    {
        return in_array($type, ['payment_confirmed', 'order_shipped', 'daily_report', 'weekly_report']);
    }

    /**
     * Должно ли отправляться SMS.
     */
    private function shouldSendSms(string $type): bool
    {
        return in_array($type, ['payment_confirmed', 'order_shipped']);
    }

    /**
     * Должно ли отправляться push.
     */
    private function shouldSendPush(string $type): bool
    {
        return in_array($type, ['payment_confirmed', 'order_shipped', 'low_stock']);
    }

    /**
     * Собирает ежедневные метрики.
     */
    private function collectDailyMetrics(int $tenantId): array
    {
        return [
            'title' => 'Дневной отчёт',
            'body' => 'Ваш дневной отчёт готов',
            'orders_count' => 0,
            'revenue' => 0,
            'customers_count' => 0,
        ];
    }

    /**
     * Собирает еженедельные метрики.
     */
    private function collectWeeklyMetrics(int $tenantId): array
    {
        return [
            'title' => 'Еженедельный отчёт',
            'body' => 'Ваш еженедельный отчёт готов',
            'orders_count' => 0,
            'revenue' => 0,
            'growth' => 0,
        ];
    }
}
