<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use App\DTO\SplitKey\InvalidateSplitKeyDTO;
use App\Events\Security\SplitKeyInvalidated;
use App\Services\Security\SplitKeyService;
use App\Services\Behavioral\BehavioralBiometricsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Invalidate Split Key on High Risk
 *
 * Listens for high-risk events and invalidates split keys accordingly.
 * Integrates with FraudControl, Behavioral Biometrics, and Insider Threat detection.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class InvalidateSplitKeyOnHighRisk implements ShouldQueue
{
    public function __construct(
        private readonly SplitKeyService $splitKeyService,
        private readonly BehavioralBiometricsService $behavioralBiometrics,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // Handle SplitKeyInvalidated event
        if ($event instanceof SplitKeyInvalidated) {
            $this->handleSplitKeyInvalidated($event);
        }

        // Handle high fraud score events
        if (method_exists($event, 'getFraudScore') && $event->getFraudScore() > 0.7) {
            $this->invalidateOnFraud($event);
        }

        // Handle behavioral anomaly events
        if (method_exists($event, 'isAnomalous') && $event->isAnomalous()) {
            $this->invalidateOnBehavioralAnomaly($event);
        }

        // Handle insider threat events
        if (method_exists($event, 'isInsiderThreat') && $event->isInsiderThreat()) {
            $this->invalidateOnInsiderThreat($event);
        }
    }

    /**
     * Handle SplitKeyInvalidated event
     */
    private function handleSplitKeyInvalidated(SplitKeyInvalidated $event): void
    {
        // Log the invalidation
        Log::channel('security')->warning('Split key invalidated', [
            'split_key_id' => $event->splitKey->id,
            'user_id' => $event->user->id,
            'reason' => $event->reason,
            'risk_level' => $event->riskLevel,
            'source' => $event->source,
        ]);

        // If critical risk, trigger additional security measures
        if ($event->riskLevel === 'critical' || $event->riskLevel === 'high') {
            $this->triggerAdditionalSecurity($event);
        }
    }

    /**
     * Invalidate split key on high fraud score
     */
    private function invalidateOnFraud(object $event): void
    {
        $userId = method_exists($event, 'getUserId') ? $event->getUserId() : null;
        $tenantId = method_exists($event, 'getTenantId') ? $event->getTenantId() : null;

        if (! $userId) {
            return;
        }

        $dto = new InvalidateSplitKeyDTO(
            userId: $userId,
            tenantId: $tenantId,
            reason: 'High fraud score detected',
            riskLevel: 'high',
            source: 'fraud',
            ipAddress: method_exists($event, 'getIpAddress') ? $event->getIpAddress() : null,
            correlationId: method_exists($event, 'getCorrelationId') ? $event->getCorrelationId() : null,
        );

        try {
            $this->splitKeyService->invalidateOnRisk($dto);

            $this->logger->warning('Split key invalidated due to high fraud score', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'fraud_score' => method_exists($event, 'getFraudScore') ? $event->getFraudScore() : null,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to invalidate split key on fraud detection', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate split key on behavioral anomaly
     */
    private function invalidateOnBehavioralAnomaly(object $event): void
    {
        $userId = method_exists($event, 'getUserId') ? $event->getUserId() : null;
        $tenantId = method_exists($event, 'getTenantId') ? $event->getTenantId() : null;

        if (! $userId) {
            return;
        }

        // Check anomaly severity
        $severity = method_exists($event, 'getAnomalySeverity') ? $event->getAnomalySeverity() : 'medium';

        // Only invalidate on critical or high severity
        if (! in_array($severity, ['critical', 'high'], true)) {
            return;
        }

        $dto = new InvalidateSplitKeyDTO(
            userId: $userId,
            tenantId: $tenantId,
            reason: 'Behavioral anomaly detected',
            riskLevel: $severity === 'critical' ? 'critical' : 'high',
            source: 'behavioral',
            ipAddress: method_exists($event, 'getIpAddress') ? $event->getIpAddress() : null,
            correlationId: method_exists($event, 'getCorrelationId') ? $event->getCorrelationId() : null,
        );

        try {
            $this->splitKeyService->invalidateOnRisk($dto);

            $this->logger->warning('Split key invalidated due to behavioral anomaly', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'severity' => $severity,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to invalidate split key on behavioral anomaly', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate split key on insider threat
     */
    private function invalidateOnInsiderThreat(object $event): void
    {
        $userId = method_exists($event, 'getUserId') ? $event->getUserId() : null;
        $tenantId = method_exists($event, 'getTenantId') ? $event->getTenantId() : null;

        if (! $userId) {
            return;
        }

        $dto = new InvalidateSplitKeyDTO(
            userId: $userId,
            tenantId: $tenantId,
            reason: 'Insider threat detected',
            riskLevel: 'critical',
            source: 'insider',
            ipAddress: method_exists($event, 'getIpAddress') ? $event->getIpAddress() : null,
            correlationId: method_exists($event, 'getCorrelationId') ? $event->getCorrelationId() : null,
        );

        try {
            $this->splitKeyService->invalidateOnRisk($dto);

            $this->logger->critical('Split key invalidated due to insider threat', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to invalidate split key on insider threat', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger additional security measures on critical risk
     */
    private function triggerAdditionalSecurity(SplitKeyInvalidated $event): void
    {
        // Log out user from all devices
        try {
            $event->user->tokens()->delete();
            $event->user->revokeAllDevices();

            $this->logger->warning('User logged out from all devices due to critical risk', [
                'user_id' => $event->user->id,
                'reason' => $event->reason,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to logout user from all devices', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Additional measures could include:
        // - Send notification to user
        // - Trigger cooldown period
        // - Flag account for manual review
        // - Notify security team
    }
}
