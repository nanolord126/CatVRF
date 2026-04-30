<?php

declare(strict_types=1);

namespace App\Services\Security;

use Psr\Log\LoggerInterface;

use App\Models\SessionRiskScore;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

final readonly class TrustDecayEngine
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

    /**
     * Calculate current trust score
     */
    public function calculateTrust(string $sessionId): float
    {
        if (! config('continuous_auth.trust_decay.enabled', true)) {
            return 100.0;
        }

        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if (! $riskScore || ! $riskScore->trust_reset_at) {
            return 100.0;
        }

        $hoursSinceReset = $riskScore->trust_reset_at->diffInHours(CarbonImmutable::now());
        $decayRate = config('continuous_auth.trust_decay.decay_rate', 0.1);

        // Trust = 100 * e^(-λ * time)
        $currentTrust = 100 * exp(-$decayRate * $hoursSinceReset);

        return max(0, min(100, $currentTrust));
    }

    /**
     * Check if re-authentication is required
     */
    public function requiresReAuth(string $sessionId): bool
    {
        $trustScore = $this->calculateTrust($sessionId);
        $threshold = config('continuous_auth.trust_decay.reauth_threshold', 50);

        return $trustScore < $threshold;
    }

    /**
     * Reset trust score (after successful re-auth)
     */
    public function resetTrust(string $sessionId): void
    {
        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if ($riskScore) {
            $riskScore->update([
                'trust_score' => 100.0,
                'trust_reset_at' => CarbonImmutable::now(),
            ]);

            $this->log->$this->logger->info('Trust score reset', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Get time until next re-auth required (in minutes)
     */
    public function getTimeUntilReAuth(string $sessionId): int
    {
        if (! config('continuous_auth.trust_decay.enabled', true)) {
            return PHP_INT_MAX;
        }

        $riskScore = SessionRiskScore::where('session_id', $sessionId)->first();

        if (! $riskScore || ! $riskScore->trust_reset_at) {
            return PHP_INT_MAX;
        }

        $threshold = config('continuous_auth.trust_decay.reauth_threshold', 50);
        $decayRate = config('continuous_auth.trust_decay.decay_rate', 0.1);

        // Solve: 100 * e^(-λ * t) = threshold
        // t = -ln(threshold/100) / λ
        $hoursToThreshold = -log($threshold / 100) / $decayRate;

        $minutesUntilThreshold = (int) round($hoursToThreshold * 60);

        return max(0, $minutesUntilThreshold);
    }
}
