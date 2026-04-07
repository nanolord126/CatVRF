<?php declare(strict_types=1);

namespace App\Services\Security;


use Illuminate\Http\Request;
use App\Services\Fraud\FraudNotificationService;

use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * Сервис мониторинга безопасности.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Архитектура:
 *   SecurityMonitoringService::logEvent() → ClickHouse + broadcast + fraud_alert канал
 *   При Critical → FraudNotificationService::notifyCriticalSecurityEvent()
 *   Реал-тайм дашборд через Laravel Echo + Redis broadcasting
 *
 * Вызывается из:
 *   AuthService     → 'login_failed', 'suspicious_login', '2fa_failed'
 *   FraudControlService → 'fraud_attempt'
 *   RateLimitingMiddleware → 'rate_limit_exceeded'
 *   WalletService   → 'suspicious_payment'
 *   AI-конструкторы → 'suspicious_ai_usage'
 */
final readonly class SecurityMonitoringService
{
    public function __construct(
        private readonly Request $request,
        private FraudNotificationService $notification,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Записать security-событие.
     *
     * @param  string $eventType  login_failed | rate_limit_exceeded | fraud_attempt | brute_force | suspicious_login | 2fa_failed | suspicious_payment | suspicious_ai_usage
     * @param  int    $userId
     * @param  array  $details    Дополнительный контекст
     * @param  string $correlationId
     * @param  float  $score      Fraud-score (0–1), если применимо
     */
    public function logEvent(
        string $eventType,
        int    $userId,
        array  $details        = [],
        string $correlationId  = '',
        float  $score          = 0.0,
    ): void {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $severity      = $this->calculateSeverity($eventType, $score, $details);

        // 1. Логируем в security канал
        $this->logger->channel('security')->info("Security event: {$eventType}", [
            'event_type'     => $eventType,
            'user_id'        => $userId,
            'severity'       => $severity,
            'score'          => $score,
            'tenant_id'      => function_exists('tenant') && tenant() ? tenant()->id : null,
            'ip_address'     => $this->request->ip(),
            'correlation_id' => $correlationId,
            'details'        => $details,
        ]);

        // 2. Сохраняем в БД (PostgreSQL)
        $this->persistEvent($eventType, $userId, $severity, $score, $details, $correlationId);

        // 3. Реал-тайм broadcast (Laravel Echo → Filament SecurityDashboard)
        $this->broadcast($eventType, $userId, $severity, $correlationId);

        // 4. Critical → мгновенное уведомление через FraudNotificationService
        if ($severity === 'critical') {
            $this->notification->notifyCriticalSecurityEvent(
                userId:        $userId,
                eventType:     $eventType,
                correlationId: $correlationId,
                details:       $details,
            );
        }
    }

    /**
     * Shortcut для логирования неудачной авторизации.
     */
    public function logFailedLogin(int $userId, string $ip, string $correlationId = ''): void
    {
        $this->logEvent('login_failed', $userId, ['ip' => $ip], $correlationId);
    }

    /**
     * Shortcut для логирования превышения rate-limit.
     */
    public function logRateLimitExceeded(int $userId, string $route, string $correlationId = ''): void
    {
        $this->logEvent('rate_limit_exceeded', $userId, ['route' => $route], $correlationId, score: 0.5);
    }

    /**
     * Shortcut для логирования fraud-события из FraudControlService.
     */
    public function logFraudAttempt(int $userId, float $score, string $operationType, string $correlationId = ''): void
    {
        $this->logEvent('fraud_attempt', $userId, ['operation_type' => $operationType], $correlationId, score: $score);
    }

    // ──────────────────────────────────────────────
    // Private
    // ──────────────────────────────────────────────

    private function calculateSeverity(string $eventType, float $score, array $details): string
    {
        if ($score > 0.85) {
            return 'critical';
        }

        if ($score > 0.65 || in_array($eventType, ['brute_force', 'suspicious_login'], true)) {
            return 'high';
        }

        $failedAttempts = (int) ($details['failed_attempts'] ?? 0);
        if ($failedAttempts > 5) {
            return 'high';
        }

        if (in_array($eventType, ['login_failed', 'rate_limit_exceeded', '2fa_failed'], true)) {
            return 'warning';
        }

        return 'info';
    }

    private function persistEvent(
        string $eventType,
        int    $userId,
        string $severity,
        float  $score,
        array  $details,
        string $correlationId,
    ): void {
        try {
            $this->db->table('security_events')->insert([
                'tenant_id'          => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id'            => $userId,
                'event_type'         => $eventType,
                'severity'           => $severity,
                'score'              => $score,
                'ip_address'         => $this->request->ip(),
                'device_fingerprint' => hash('sha256', $this->request->ip() . $this->request->userAgent()),
                'details'            => json_encode($details, JSON_UNESCAPED_UNICODE),
                'correlation_id'     => $correlationId,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        } catch (\Throwable $e) {
            // Не ломаем основной поток если security_events таблица недоступна
            $this->logger->channel('security')->error('Failed to persist security event', [
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }
    }

    private function broadcast(
        string $eventType,
        int    $userId,
        string $severity,
        string $correlationId,
    ): void {
        try {
            // Канал для Admin/Tenant Panel (SecurityDashboard Filament)
            $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 0;

            \Illuminate\Support\Facades\Event::dispatch(
                \App\Events\SecurityEventOccurred::now(
                    eventType:     $eventType,
                    userId:        $userId,
                    severity:      $severity,
                    correlationId: $correlationId,
                    tenantId:      (int) $tenantId,
                )
            );
        } catch (\Throwable $e) {
            // Broadcast не должен ломать основной поток
            $this->logger->channel('security')->warning('Security event broadcast failed', [
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }
    }
}
