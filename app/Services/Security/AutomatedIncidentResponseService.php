<?php

declare(strict_types=1);

namespace App\Services\Security;

use Psr\Log\LoggerInterface;

use App\Models\IncidentResponse;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class AutomatedIncidentResponseService
{
    private const CACHE_TTL_MINUTES = 1;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly SIEMService $siemService,
        private readonly AuditService $audit,
        private readonly CacheManager $cache,
        private readonly LogManager $log,) {}

    /**
     * Handle automatic incident response based on security event
     */
    public function handleEvent(SecurityEvent $event): ?IncidentResponse
    {
        $response = null;

        switch ($event->event_type) {
            case 'fraud_detected':
                $response = $this->handleFraudDetected($event);
                break;

            case 'brute_force':
                $response = $this->handleBruteForce($event);
                break;

            case 'credential_stuffing':
                $response = $this->handleCredentialStuffing($event);
                break;

            case 'aml_alert':
                $response = $this->handleAMLAlert($event);
                break;

            case 'data_breach_attempt':
                $response = $this->handleDataBreachAttempt($event);
                break;

            case 'insider_threat':
                $response = $this->handleInsiderThreat($event);
                break;
        }

        if ($response) {
            $this->clearCache();
        }

        return $response;
    }

    /**
     * Rollback an incident response
     */
    public function rollbackResponse(int $responseId, string $rolledBackBy, ?string $notes = null): bool
    {
        $response = IncidentResponse::findOrFail($responseId);

        if (! $response->isRollable()) {
            return false;
        }

        try {
            $rolledBack = $this->executeRollback($response);

            $response->update([
                'rolled_back_at' => CarbonImmutable::now(),
                'auto_rollback' => false,
            ]);

            // Log audit
            $this->audit->record(
                action: 'incident_response_rolled_back',
                subjectType: IncidentResponse::class,
                subjectId: $response->id,
                newValues: [
                    'rolled_back_by' => $rolledBackBy,
                    'notes' => $notes,
                    'success' => $rolledBack,
                ],
            );

            $this->log->$this->logger->info('Incident response rolled back', [
                'response_id' => $response->response_id,
                'rolled_back_by' => $rolledBackBy,
            ]);

            return $rolledBack;
        } catch (\Exception $e) {
            $this->log->error('Failed to rollback incident response', [
                'response_id' => $response->response_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process pending auto-rollbacks
     */
    public function processAutoRollbacks(): int
    {
        $responses = IncidentResponse::executed()
            ->where('auto_rollback', true)
            ->whereNotNull('rollback_after_minutes')
            ->whereNull('rolled_back_at')
            ->get();

        $rolledBack = 0;

        foreach ($responses as $response) {
            if ($response->shouldRollback()) {
                if ($this->rollbackResponse($response->id, 'system', 'Auto-rollback based on timer')) {
                    $rolledBack++;
                }
            }
        }

        return $rolledBack;
    }

    /**
     * Get incident response statistics
     */
    public function getStatistics(int $hours = 24): array
    {
        $cacheKey = "incident_response:stats:{$hours}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL_MINUTES * 60, function () use ($hours) {
            $since = CarbonImmutable::now()->subHours($hours);

            return [
                'total_responses' => IncidentResponse::where('executed_at', '>=', $since)->count(),
                'executed' => IncidentResponse::where('executed_at', '>=', $since)->where('status', 'executed')->count(),
                'failed' => IncidentResponse::where('executed_at', '>=', $since)->where('status', 'failed')->count(),
                'rolled_back' => IncidentResponse::where('executed_at', '>=', $since)->whereNotNull('rolled_back_at')->count(),
                'by_trigger_type' => IncidentResponse::where('executed_at', '>=', $since)
                    ->selectRaw('trigger_type, COUNT(*) as count')
                    ->groupBy('trigger_type')
                    ->pluck('count', 'trigger_type')
                    ->toArray(),
            ];
        });
    }

    /**
     * Handle fraud detected event - freeze wallet
     */
    private function handleFraudDetected(SecurityEvent $event): ?IncidentResponse
    {
        if ($event->severity !== 'critical') {
            return new IncidentResponse(['status' => 'skipped', 'reason' => 'Fraud severity not critical']);
        }

        $userId = $event->user_id;
        if (! $userId) {
            return new IncidentResponse(['status' => 'failed', 'reason' => 'No user ID available']);
        }

        try {
            // Auto-freeze user wallet
            $frozen = $this->freezeUserWallet($userId);

            $response = IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'user_id' => $userId,
                'tenant_id' => $event->tenant_id,
                'trigger_type' => 'auto_freeze_wallet',
                'trigger_condition' => 'fraud_detected',
                'trigger_data' => $event->metadata,
                'action_taken' => 'wallet_frozen',
                'action_data' => [
                    'wallet_frozen' => $frozen,
                    'reason' => 'Fraud detected with high confidence',
                ],
                'status' => $frozen ? 'executed' : 'failed',
                'executed_at' => CarbonImmutable::now(),
                'auto_rollback' => true,
                'rollback_after_minutes' => 60, // Auto-rollback after 1 hour
                'correlation_id' => $event->correlation_id,
            ]);

            // Log audit
            $this->audit->record(
                action: 'auto_freeze_wallet',
                subjectType: IncidentResponse::class,
                subjectId: $response->id,
                newValues: [
                    'user_id' => $userId,
                    'trigger' => 'fraud_detected',
                    'auto_rollback' => true,
                ],
                correlationId: $event->correlation_id,
            );

            $this->log->$this->logger->info('Auto-incident response: Wallet frozen due to fraud', [
                'user_id' => $userId,
                'event_id' => $event->event_id,
                'response_id' => $response->response_id,
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->log->error('Failed to execute auto-incident response: freeze wallet', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'user_id' => $userId,
                'tenant_id' => $event->tenant_id,
                'trigger_type' => 'auto_freeze_wallet',
                'trigger_condition' => 'fraud_detected',
                'trigger_data' => $event->metadata,
                'action_taken' => 'wallet_frozen',
                'action_data' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'executed_at' => CarbonImmutable::now(),
                'correlation_id' => $event->correlation_id,
            ]);

            return null;
        }
    }

    /**
     * Handle brute force event - block IP
     */
    private function handleBruteForce(SecurityEvent $event): ?IncidentResponse
    {
        if ($event->severity !== 'critical') {
            return null;
        }

        $sourceIp = $event->source_ip;
        if (! $sourceIp) {
            return null;
        }

        try {
            // Block IP address
            $blocked = $this->blockIpAddress($sourceIp);

            $response = IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'user_id' => $event->user_id,
                'tenant_id' => $event->tenant_id,
                'trigger_type' => 'auto_block_ip',
                'trigger_condition' => 'brute_force',
                'trigger_data' => $event->metadata,
                'action_taken' => 'ip_blocked',
                'action_data' => [
                    'ip_address' => $sourceIp,
                    'blocked' => $blocked,
                    'duration_minutes' => 60, // Block for 1 hour
                ],
                'status' => $blocked ? 'executed' : 'failed',
                'executed_at' => CarbonImmutable::now(),
                'auto_rollback' => true,
                'rollback_after_minutes' => 60,
                'correlation_id' => $event->correlation_id,
            ]);

            // Log audit
            $this->audit->record(
                action: 'auto_block_ip',
                subjectType: IncidentResponse::class,
                subjectId: $response->id,
                newValues: [
                    'ip_address' => $sourceIp,
                    'trigger' => 'brute_force',
                    'auto_rollback' => true,
                ],
                correlationId: $event->correlation_id,
            );

            $this->log->$this->logger->info('Auto-incident response: IP blocked due to brute force', [
                'ip_address' => $sourceIp,
                'event_id' => $event->event_id,
                'response_id' => $response->response_id,
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->log->error('Failed to execute auto-incident response: block IP', [
                'ip_address' => $sourceIp,
                'error' => $e->getMessage(),
            ]);

            IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'trigger_type' => 'auto_block_ip',
                'trigger_condition' => 'brute_force',
                'trigger_data' => $event->metadata,
                'action_taken' => 'ip_blocked',
                'action_data' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'executed_at' => CarbonImmutable::now(),
                'correlation_id' => $event->correlation_id,
            ]);

            return null;
        }
    }

    /**
     * Handle credential stuffing event - revoke tokens
     */
    private function handleCredentialStuffing(SecurityEvent $event): ?IncidentResponse
    {
        if ($event->severity !== 'critical') {
            return null;
        }

        $userId = $event->user_id;
        if (! $userId) {
            return null;
        }

        try {
            // Revoke all user tokens
            $revoked = $this->revokeUserTokens($userId);

            $response = IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'user_id' => $userId,
                'tenant_id' => $event->tenant_id,
                'trigger_type' => 'auto_revoke_token',
                'trigger_condition' => 'credential_stuffing',
                'trigger_data' => $event->metadata,
                'action_taken' => 'tokens_revoked',
                'action_data' => [
                    'user_id' => $userId,
                    'tokens_revoked' => $revoked,
                ],
                'status' => 'executed',
                'executed_at' => CarbonImmutable::now(),
                'auto_rollback' => false, // No auto-rollback for token revocation
                'correlation_id' => $event->correlation_id,
            ]);

            // Log audit
            $this->audit->record(
                action: 'auto_revoke_tokens',
                subjectType: IncidentResponse::class,
                subjectId: $response->id,
                newValues: [
                    'user_id' => $userId,
                    'trigger' => 'credential_stuffing',
                    'tokens_revoked' => $revoked,
                ],
                correlationId: $event->correlation_id,
            );

            $this->log->$this->logger->info('Auto-incident response: Tokens revoked due to credential stuffing', [
                'user_id' => $userId,
                'event_id' => $event->event_id,
                'response_id' => $response->response_id,
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->log->error('Failed to execute auto-incident response: revoke tokens', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'user_id' => $userId,
                'tenant_id' => $event->tenant_id,
                'trigger_type' => 'auto_revoke_token',
                'trigger_condition' => 'credential_stuffing',
                'trigger_data' => $event->metadata,
                'action_taken' => 'tokens_revoked',
                'action_data' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'executed_at' => CarbonImmutable::now(),
                'correlation_id' => $event->correlation_id,
            ]);

            return null;
        }
    }

    /**
     * Handle AML alert - escalate to SOC
     */
    private function handleAMLAlert(SecurityEvent $event): ?IncidentResponse
    {
        if ($event->severity !== 'critical') {
            return null;
        }

        try {
            // Escalate to SOC
            $escalated = $this->escalateToSOC($event);

            $response = IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'user_id' => $event->user_id,
                'tenant_id' => $event->tenant_id,
                'trigger_type' => 'auto_escalate',
                'trigger_condition' => 'aml_alert',
                'trigger_data' => $event->metadata,
                'action_taken' => 'escalated',
                'action_data' => [
                    'escalated_to' => 'SOC',
                    'priority' => 'high',
                    'channels' => ['slack', 'telegram'],
                ],
                'status' => $escalated ? 'executed' : 'failed',
                'executed_at' => CarbonImmutable::now(),
                'auto_rollback' => false,
                'correlation_id' => $event->correlation_id,
            ]);

            // Log audit
            $this->audit->record(
                action: 'auto_escalate_soc',
                subjectType: IncidentResponse::class,
                subjectId: $response->id,
                newValues: [
                    'trigger' => 'aml_alert',
                    'priority' => 'high',
                ],
                correlationId: $event->correlation_id,
            );

            $this->log->$this->logger->info('Auto-incident response: Escalated to SOC due to AML alert', [
                'event_id' => $event->event_id,
                'response_id' => $response->response_id,
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->log->error('Failed to execute auto-incident response: escalate to SOC', [
                'error' => $e->getMessage(),
            ]);

            IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'user_id' => $event->user_id,
                'tenant_id' => $event->tenant_id,
                'trigger_type' => 'auto_escalate',
                'trigger_condition' => 'aml_alert',
                'trigger_data' => $event->metadata,
                'action_taken' => 'escalated',
                'action_data' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'executed_at' => CarbonImmutable::now(),
                'correlation_id' => $event->correlation_id,
            ]);

            return null;
        }
    }

    /**
     * Handle data breach attempt - block user and escalate
     */
    private function handleDataBreachAttempt(SecurityEvent $event): ?IncidentResponse
    {
        if ($event->severity !== 'critical') {
            return null;
        }

        $userId = $event->user_id;
        if (! $userId) {
            return null;
        }

        try {
            // Block user account
            $blocked = $this->blockUserAccount($userId);

            // Escalate to SOC
            $escalated = $this->escalateToSOC($event);

            $response = IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'user_id' => $userId,
                'tenant_id' => $event->tenant_id,
                'trigger_type' => 'auto_block_user',
                'trigger_condition' => 'data_breach_attempt',
                'trigger_data' => $event->metadata,
                'action_taken' => 'user_blocked_and_escalated',
                'action_data' => [
                    'user_blocked' => $blocked,
                    'escalated_to' => 'SOC',
                    'priority' => 'critical',
                ],
                'status' => ($blocked && $escalated) ? 'executed' : 'failed',
                'executed_at' => CarbonImmutable::now(),
                'auto_rollback' => false,
                'correlation_id' => $event->correlation_id,
            ]);

            // Log audit
            $this->audit->record(
                action: 'auto_block_user_escalate',
                subjectType: IncidentResponse::class,
                subjectId: $response->id,
                newValues: [
                    'user_id' => $userId,
                    'trigger' => 'data_breach_attempt',
                    'priority' => 'critical',
                ],
                correlationId: $event->correlation_id,
            );

            $this->log->$this->logger->info('Auto-incident response: User blocked and escalated due to data breach attempt', [
                'user_id' => $userId,
                'event_id' => $event->event_id,
                'response_id' => $response->response_id,
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->log->error('Failed to execute auto-incident response: block user and escalate', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'user_id' => $userId,
                'tenant_id' => $event->tenant_id,
                'trigger_type' => 'auto_block_user',
                'trigger_condition' => 'data_breach_attempt',
                'trigger_data' => $event->metadata,
                'action_taken' => 'user_blocked_and_escalated',
                'action_data' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'executed_at' => CarbonImmutable::now(),
                'correlation_id' => $event->correlation_id,
            ]);

            return null;
        }
    }

    /**
     * Handle insider threat - escalate to SOC
     */
    private function handleInsiderThreat(SecurityEvent $event): ?IncidentResponse
    {
        if ($event->severity !== 'critical') {
            return null;
        }

        try {
            // Escalate to SOC with high priority
            $escalated = $this->escalateToSOC($event, 'critical');

            $response = IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'user_id' => $event->user_id,
                'tenant_id' => $event->tenant_id,
                'trigger_type' => 'auto_escalate',
                'trigger_condition' => 'insider_threat',
                'trigger_data' => $event->metadata,
                'action_taken' => 'escalated',
                'action_data' => [
                    'escalated_to' => 'SOC',
                    'priority' => 'critical',
                    'channels' => ['slack', 'telegram', 'pagerduty'],
                ],
                'status' => $escalated ? 'executed' : 'failed',
                'executed_at' => CarbonImmutable::now(),
                'auto_rollback' => false,
                'correlation_id' => $event->correlation_id,
            ]);

            // Log audit
            $this->audit->record(
                action: 'auto_escalate_insider_threat',
                subjectType: IncidentResponse::class,
                subjectId: $response->id,
                newValues: [
                    'trigger' => 'insider_threat',
                    'priority' => 'critical',
                ],
                correlationId: $event->correlation_id,
            );

            $this->log->$this->logger->info('Auto-incident response: Escalated to SOC due to insider threat', [
                'event_id' => $event->event_id,
                'response_id' => $response->response_id,
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->log->error('Failed to execute auto-incident response: escalate insider threat', [
                'error' => $e->getMessage(),
            ]);

            IncidentResponse::create([
                'response_id' => Str::uuid()->toString(),
                'security_event_id' => $event->id,
                'user_id' => $event->user_id,
                'tenant_id' => $event->tenant_id,
                'trigger_type' => 'auto_escalate',
                'trigger_condition' => 'insider_threat',
                'trigger_data' => $event->metadata,
                'action_taken' => 'escalated',
                'action_data' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'executed_at' => CarbonImmutable::now(),
                'correlation_id' => $event->correlation_id,
            ]);

            return null;
        }
    }

    /**
     * Execute rollback based on response type
     */
    private function executeRollback(IncidentResponse $response): bool
    {
        switch ($response->trigger_type) {
            case 'auto_freeze_wallet':
                return $this->unfreezeUserWallet($response->user_id);

            case 'auto_block_ip':
                return $this->unblockIpAddress($response->action_data['ip_address'] ?? null);

            case 'auto_block_user':
                return $this->unblockUserAccount($response->user_id);

            default:
                return true; // No rollback needed for other types
        }
    }

    // Helper methods (these would integrate with actual services)

    private function freezeUserWallet(int $userId): bool
    {
        // TODO: Integrate with WalletService
        // For now, return true as placeholder
        return true;
    }

    private function unfreezeUserWallet(int $userId): bool
    {
        // TODO: Integrate with WalletService
        return true;
    }

    private function blockIpAddress(string $ip): bool
    {
        // Интеграция с фаерволом и rate-limiting
        // Store in Redis or database
        $this->cache->put("blocked_ip:{$ip}", true, 3600); // Block for 1 hour

        return true;
    }

    private function unblockIpAddress(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }
        $this->cache->forget("blocked_ip:{$ip}");

        return true;
    }

    private function revokeUserTokens(int $userId): int
    {
        // Управление Sanctum токенами пользователя
        return 0; // Placeholder
    }

    private function blockUserAccount(int $userId): bool
    {
        // TODO: Integrate with User model
        return true;
    }

    private function unblockUserAccount(int $userId): bool
    {
        // TODO: Integrate with User model
        return true;
    }

    private function escalateToSOC(SecurityEvent $event, string $priority = 'high'): bool
    {
        // Отправка алертов в Slack/Telegram/PagerDuty
        // For now, log the escalation
        $this->log->$this->logger->info('Escalating to SOC', [
            'event_id' => $event->event_id,
            'priority' => $priority,
            'event_type' => $event->event_type,
        ]);

        return true;
    }

    private function clearCache(): void
    {
        $this->cache->forget('incident_response:stats:1');
        $this->cache->forget('incident_response:stats:24');
        $this->cache->forget('incident_response:stats:168');
    }
}
