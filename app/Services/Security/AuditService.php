<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Database\DatabaseManager;

use Psr\Log\LoggerInterface;

use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class AuditService
{
    public function __construct(private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}
    private const CHANNEL_SECURITY = 'security';

    private const CHANNEL_AUDIT = 'audit';

    /**
     * Log security event with full context
     */
    public function logEvent(
        string $eventType,
        array $context,
        string $channel = self::CHANNEL_AUDIT,
        ?string $correlationId = null
    ): void {
        $correlationId = $correlationId ?? (string) Str::uuid();

        $logData = array_merge($context, [
            'event_type' => $eventType,
            'correlation_id' => $correlationId,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'environment' => config('app.env'),
        ]);

        // Mask sensitive data
        $logData = $this->maskSensitiveData($logData);

        // Log to Laravel
        $this->log->channel($channel)->$this->logger->info($eventType, $logData);

        // In production, also send to ClickHouse for immutable audit log
        $this->sendToClickHouse($eventType, $logData, $channel);
    }

    /**
     * Log authentication event
     */
    public function logAuthEvent(
        string $action,
        int $userId,
        ?int $tenantId,
        bool $success,
        ?string $failureReason = null,
        array $additionalContext = []
    ): void {
        $this->logEvent('auth_'.$action, array_merge($additionalContext, [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'success' => $success,
            'failure_reason' => $failureReason,
        ]), self::CHANNEL_SECURITY);
    }

    /**
     * Log passkey event
     */
    public function logPasskeyEvent(
        string $action,
        int $userId,
        ?int $tenantId,
        ?string $credentialId = null,
        array $additionalContext = []
    ): void {
        $this->logEvent('passkey_'.$action, array_merge($additionalContext, [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'credential_id' => $credentialId,
        ]), self::CHANNEL_SECURITY);
    }

    /**
     * Log recovery event
     */
    public function logRecoveryEvent(
        string $stage,
        int $userId,
        ?int $tenantId,
        string $method,
        float $riskScore,
        array $additionalContext = []
    ): void {
        $this->logEvent('recovery_'.$stage, array_merge($additionalContext, [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'method' => $method,
            'risk_score' => $riskScore,
        ]), self::CHANNEL_SECURITY);
    }

    /**
     * Log fraud detection event
     */
    public function logFraudEvent(
        string $detectionType,
        int $userId,
        ?int $tenantId,
        bool $blocked,
        float $fraudScore,
        array $additionalContext = []
    ): void {
        $this->logEvent('fraud_'.$detectionType, array_merge($additionalContext, [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'blocked' => $blocked,
            'fraud_score' => $fraudScore,
        ]), self::CHANNEL_SECURITY);
    }

    /**
     * Log device event
     */
    public function logDeviceEvent(
        string $action,
        int $userId,
        ?int $tenantId,
        string $deviceFingerprint,
        array $additionalContext = []
    ): void {
        $this->logEvent('device_'.$action, array_merge($additionalContext, [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'device_fingerprint' => $deviceFingerprint,
        ]), self::CHANNEL_SECURITY);
    }

    /**
     * Query audit logs for a user
     */
    public function getUserAuditLogs(int $userId, int $limit = 100): array
    {
        // In production, query ClickHouse
        // For now, return empty array

        return [];
    }

    /**
     * Query security events for a tenant
     */
    public function getTenantSecurityEvents(?int $tenantId, int $limit = 100): array
    {
        // In production, query ClickHouse
        // For now, return empty array

        return [];
    }

    /**
     * Mask sensitive data before logging
     */
    private function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'otp',
            'token',
            'secret',
            'api_key',
            'credential_public_key',
            'backup_codes',
            'face_image',
            'base64',
        ];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            } elseif (is_string($value) && is_string($key)) {
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (str_contains(strtolower($key), $sensitiveKey)) {
                        $data[$key] = '[REDACTED]';
                        break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Send audit event to ClickHouse for immutable storage
     */
    private function sendToClickHouse(string $eventType, array $data, string $channel): void
    {
        // In production, this would insert into ClickHouse
        // For now, we'll just log it

        try {
            // Example ClickHouse insertion:
            // $this->db->connection('clickhouse')
            //     ->table('security_events')
            //     ->insert([
            //         'event_type' => $eventType,
            //         'channel' => $channel,
            //         'data' => json_encode($data),
            //         'created_at' => CarbonImmutable::now(),
            //     ]);
        } catch (\Exception $e) {
            $this->log->error('Failed to send audit event to ClickHouse', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
