<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Events\Security\AccountLocked;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Fraud\FraudControlService;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

final class AccountProtectionService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const LOCK_DURATION_HOURS = 24;
    private const MAX_FAILED_ATTEMPTS = 5;
    private const GEO_JUMP_THRESHOLD_KM = 1000;
    private const VELOCITY_WINDOW_SECONDS = 300; // 5 minute window

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly FraudControlService $fraudControlService,
        private readonly AuditService $auditService,
        private readonly CacheManager $cache,
        private readonly LogManager $log,) {}

    /**
     * Detect anomalies in authentication attempt
     */
    public function detectAnomaly(User $user, string $ipAddress, string $userAgent, string $deviceFingerprint): array
    {
        $anomalies = [];
        $riskScore = 0.0;

        // Check device fingerprint
        $device = UserDevice::where('user_id', $user->id)
            ->where('fingerprint', $deviceFingerprint)
            ->first();

        $isNewDevice = $device === null;
        if ($isNewDevice) {
            $anomalies[] = 'new_device';
            $riskScore += 0.30;
        }

        // Check IP reputation via FraudControl
        $ipRisk = $this->fraudControlService->checkIpReputation($ipAddress);
        if ($ipRisk['is_suspicious'] ?? false) {
            $anomalies[] = 'suspicious_ip';
            $riskScore += 0.40;
        }

        // Check velocity (too many attempts in short time)
        $velocityRisk = $this->checkVelocity($user->id, $ipAddress);
        if ($velocityRisk['is_high_velocity'] ?? false) {
            $anomalies[] = 'high_velocity';
            $riskScore += 0.35;
        }

        // Check geo-jump (if location data available)
        if ($device && $device->location_country) {
            $geoJumpRisk = $this->checkGeoJump($user, $ipAddress);
            if ($geoJumpRisk['is_geo_jump'] ?? false) {
                $anomalies[] = 'geo_jump';
                $riskScore += 0.25;
            }
        }

        // Check unusual time (3 AM - 6 AM local time)
        $unusualTimeRisk = $this->checkUnusualTime($user);
        if ($unusualTimeRisk['is_unusual'] ?? false) {
            $anomalies[] = 'unusual_time';
            $riskScore += 0.15;
        }

        // Clamp risk score to 1.0
        $riskScore = min(1.0, $riskScore);

        $this->auditService->logEvent(
            'anomaly_detection',
            [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'anomalies' => $anomalies,
                'risk_score' => $riskScore,
                'ip_address' => $ipAddress,
                'device_fingerprint' => $deviceFingerprint,
            ]
        );

        return [
            'has_anomaly' => count($anomalies) > 0,
            'anomalies' => $anomalies,
            'risk_score' => $riskScore,
            'requires_additional_verification' => $riskScore >= 0.40,
            'should_block' => $riskScore >= 0.80,
        ];
    }

    /**
     * Lock user account due to suspicious activity
     */
    public function lockAccount(User $user, string $reason, array $metadata = []): void
    {
        $lockKey = "account_locked:{$user->id}";

        $this->cache->put($lockKey, [
            'reason' => $reason,
            'locked_at' => CarbonImmutable::now()->toIso8601String(),
            'metadata' => $metadata,
        ], self::LOCK_DURATION_HOURS * 3600);

        $user->update(['is_locked' => true]);

        $this->auditService->logEvent('account_locked', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'reason' => $reason,
            'metadata' => $metadata,
        ], 'security');

        $this->log->warning('Account locked', [
            'user_id' => $user->id,
            'reason' => $reason,
        ]);

        $this->eventDispatcher->dispatch(new AccountLocked($user, $reason));
    }

    /**
     * Unlock user account
     */
    public function unlockAccount(User $user, ?string $unlockedBy = null): void
    {
        $lockKey = "account_locked:{$user->id}";
        $this->cache->forget($lockKey);

        $user->update(['is_locked' => true]);

        $this->auditService->logEvent('account_unlocked', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'unlocked_by' => $unlockedBy,
        ], 'security');
    }

    /**
     * Check if account is locked
     */
    public function isAccountLocked(User $user): bool
    {
        $lockKey = "account_locked:{$user->id}";

        return $this->cache->has($lockKey) || $user->is_locked ?? false;
    }

    /**
     * Determine if additional verification is required
     */
    public function requiresAdditionalVerification(User $user, float $riskScore): bool
    {
        // Always require for high risk
        if ($riskScore >= 0.40) {
            return true;
        }

        // Require for new devices
        $recentDevices = UserDevice::where('user_id', $user->id)
            ->where('created_at', '>=', CarbonImmutable::now()->subHours(24))
            ->count();

        if ($recentDevices === 0) {
            return true;
        }

        // Require for sensitive operations (check context)
        // This would be called with operation context in real implementation
        return false;
    }

    /**
     * Increment failed authentication counter
     */
    public function incrementFailedAttempts(User $user, string $ipAddress): void
    {
        $key = "failed_auth:{$user->id}:{$ipAddress}";
        $attempts = $this->cache->increment($key, 1, 900); // 15 minute window

        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            $this->lockAccount($user, 'too_many_failed_attempts', [
                'ip_address' => $ipAddress,
                'attempts' => $attempts,
            ]);
        }
    }

    /**
     * Reset failed authentication counter
     */
    public function resetFailedAttempts(User $user, string $ipAddress): void
    {
        $key = "failed_auth:{$user->id}:{$ipAddress}";
        $this->cache->forget($key);
    }

    /**
     * Check velocity of authentication attempts
     */
    private function checkVelocity(int $userId, string $ipAddress): array
    {
        $key = "auth_velocity:{$userId}:{$ipAddress}";
        $attempts = $this->cache->get($key, 0);

        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            return [
                'is_high_velocity' => true,
                'attempts' => $attempts,
            ];
        }

        $this->cache->increment($key, 1, self::VELOCITY_WINDOW_SECONDS);

        return [
            'is_high_velocity' => false,
            'attempts' => $attempts + 1,
        ];
    }

    /**
     * Check for geo-jump (impossible travel)
     */
    private function checkGeoJump(User $user, string $currentIp): array
    {
        // This would integrate with a GeoIP service
        // For now, return false (would be implemented with MaxMind or similar)
        return [
            'is_geo_jump' => false,
        ];
    }

    /**
     * Check if authentication time is unusual
     */
    private function checkUnusualTime(User $user): array
    {
        $hour = CarbonImmutable::now()->hour;

        // Consider 3 AM - 6 AM as unusual
        if ($hour >= 3 && $hour < 6) {
            return [
                'is_unusual' => true,
                'hour' => $hour,
            ];
        }

        return [
            'is_unusual' => false,
            'hour' => $hour,
        ];
    }
}
