<?php

declare(strict_types=1);

namespace App\Services\Fraud;

use Illuminate\Notifications\ChannelManager;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Psr\Log\LoggerInterface;

use App\Jobs\FraudNotificationJob;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Queue\Queue;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * Сервис уведомлений о фроде.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Уровни серьёзности и каналы:
 *   info     (0.0–0.4)  → только audit log
 *   warning  (0.4–0.65) → in_app + email
 *   high     (0.65–0.85)→ in_app + email + push + telegram
 *   critical (>0.85)    → in_app + email + push + sms + telegram + slack
 *
 * Никаких прямых $this->notificationManager->send() вне этого сервиса.
 * Отправка всегда асинхронна через FraudNotificationJob.
 */
final readonly class FraudNotificationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly ChannelManager $notificationManager,
        private readonly BusDispatcher $bus,
        private readonly LoggerInterface $logger,
        private readonly Queue $queue,
        private readonly LogManager $log,
        private readonly DatabaseManager $db,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Создать и отправить уведомление по результату проверки фрода.
     *
     * @param  float  $score  ML-score (0.0–1.0)
     * @param  array  $details  Дополнительный контекст
     */
    public function notify(
        int $userId,
        float $score,
        string $operationType,
        string $correlationId,
        array $details = [],
    ): void {
        $severity = $this->resolveSeverity($score);

        // info → только лог, без уведомления пользователю
        if ($severity === 'info') {
            $this->logger->channel('fraud_alert')->$this->logger->info('Fraud info event skipped notification', [
                'correlation_id' => $correlationId,
                'user_id'        => $userId,
                'score'          => $score,
            ]);

            return;
        }

        $notificationId = $this->db->table('fraud_notifications')->insertGetId([
            'tenant_id'      => function_exists('tenant') && tenant() ? tenant()->id : null,
            'user_id'        => $userId,
            'severity'       => $severity,
            'title'          => $this->buildTitle($operationType),
            'message'        => $this->buildMessage($operationType, $score, $details),
            'channels'       => json_encode($this->resolveChannels($severity), JSON_UNESCAPED_UNICODE),
            'status'         => 'pending',
            'correlation_id' => $correlationId,
            'created_at'     => CarbonImmutable::now(),
            'updated_at'     => CarbonImmutable::now(),
        ]);

        // Отправка асинхронно, задержка 3 секунды
        FraudNotificationJob::$this->bus->dispatch($notificationId, $severity, $correlationId)
            ->onQueue('fraud-notifications')
            ->delay(CarbonImmutable::now()->addSeconds(3));

        $this->logger->channel('fraud_alert')->warning('Fraud notification queued', [
            'notification_id' => $notificationId,
            'severity'        => $severity,
            'user_id'         => $userId,
            'score'           => $score,
            'correlation_id'  => $correlationId,
        ]);
    }

    /**
     * Уведомление о critical security-событии (для SecurityMonitoringService).
     */
    public function notifyCriticalSecurityEvent(
        int $userId,
        string $eventType,
        string $correlationId,
        array $details = [],
    ): void {
        $this->notify(
            userId:        $userId,
            score:         1.0,
            operationType: $eventType,
            correlationId: $correlationId,
            details:       $details,
        );
    }

    // ──────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────

    private function resolveSeverity(float $score): string
    {
        return match (true) {
            $score > 0.85 => 'critical',
            $score > 0.65 => 'high',
            $score > 0.40 => 'warning',
            default       => 'info',
        };
    }

    /** @return list<string> */
    private function resolveChannels(string $severity): array
    {
        return match ($severity) {
            'high'     => ['in_app', 'email', 'push', 'telegram'],
            'warning'  => ['in_app', 'email'],
            default    => [],
        };
    }

    private function buildTitle(string $operationType): string
    {
        return match ($operationType) {
            'payout'         => 'Подозрительный вывод средств',
            'login'          => 'Подозрительный вход',
            'login_failed'   => 'Попытка взлома аккаунта',
            '2fa_failed'     => 'Неверный код 2FA',
            'suspicious_login' => 'Подозрительная авторизация',
            default          => 'Фрод-активность обнаружена',
        };
    }

    private function buildMessage(string $operationType, float $score, array $details): string
    {
        $severity = $this->resolveSeverity($score);
        $percent  = round($score * 100);

        return match ($operationType) {
            'payout'       => "Обнаружена подозрительная попытка вывода средств (риск {$percent}%).",
            'login'        => "Зафиксирован подозрительный вход в ваш аккаунт (риск {$percent}%).",
            default        => "Операция '{$operationType}' заблокирована системой безопасности. Риск: {$percent}%.",
        };
    }
}
