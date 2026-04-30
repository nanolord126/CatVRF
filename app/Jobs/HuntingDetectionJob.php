<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\Security\CooldownService;
use App\Services\Security\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Hunting Detection Job
 * 
 * Queued job for analyzing query patterns to detect hunting behavior.
 * Performs deep analysis without blocking the main request.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Data Exfiltration Fortress.
 */
final readonly class HuntingDetectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CACHE_TTL_HOURS = 24;
    private const SCORE_THRESHOLD = 0.7;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $userId,
        private readonly int $tenantId,
        private readonly array $queryData,
        private readonly string $correlationId,
    ) {
        $this->onQueue('security');
    }

    /**
     * Execute the job.
     */
    public function handle(
        CooldownService $cooldownService,
        AuditService $auditService,
    ): void {
        $user = User::find($this->userId);
        if (! $user) {
            Log::warning('HuntingDetectionJob: User not found', ['user_id' => $this->userId]);
            return;
        }

        // Calculate hunting score
        $score = $this->calculateHuntingScore();

        if ($score >= self::SCORE_THRESHOLD) {
            $this->handleHighRiskUser($user, $cooldownService, $auditService, $score);
        } else {
            $this->handleLowRiskUser($user, $auditService, $score);
        }
    }

    /**
     * Calculate hunting score based on query patterns
     */
    private function calculateHuntingScore(): float
    {
        $score = 0.0;

        // Factor 1: Query frequency (0-0.3)
        $score += $this->calculateFrequencyScore();

        // Factor 2: Pattern matching on contact fields (0-0.4)
        $score += $this->calculatePatternScore();

        // Factor 3: Result size (0-0.2)
        $score += $this->calculateResultSizeScore();

        // Factor 4: Time of day anomaly (0-0.1)
        $score += $this->calculateTimeAnomalyScore();

        return min($score, 1.0);
    }

    /**
     * Calculate frequency score based on recent queries
     */
    private function calculateFrequencyScore(): float
    {
        $cacheKey = "hunting_frequency:{$this->userId}";
        $recentQueries = Cache::get($cacheKey, 0);

        if ($recentQueries > 50) {
            return 0.3;
        } elseif ($recentQueries > 20) {
            return 0.2;
        } elseif ($recentQueries > 10) {
            return 0.1;
        }

        return 0.0;
    }

    /**
     * Calculate pattern score based on LIKE queries
     */
    private function calculatePatternScore(): float
    {
        $patternCount = 0;
        $totalQueries = count($this->queryData);

        foreach ($this->queryData as $query) {
            $sql = $query['sql'] ?? '';
            if (preg_match('/\bLIKE\b/i', $sql)) {
                $patternCount++;
            }
        }

        if ($totalQueries === 0) {
            return 0.0;
        }

        $ratio = $patternCount / $totalQueries;

        return match (true) {
            $ratio > 0.8 => 0.4,
            $ratio > 0.5 => 0.3,
            $ratio > 0.3 => 0.2,
            $ratio > 0.1 => 0.1,
            default => 0.0,
        };
    }

    /**
     * Calculate result size score
     */
    private function calculateResultSizeScore(): float
    {
        $totalRows = array_sum(array_column($this->queryData, 'rows_returned'));

        if ($totalRows > 1000) {
            return 0.2;
        } elseif ($totalRows > 500) {
            return 0.15;
        } elseif ($totalRows > 100) {
            return 0.1;
        }

        return 0.0;
    }

    /**
     * Calculate time anomaly score (queries at unusual hours)
     */
    private function calculateTimeAnomalyScore(): float
    {
        $hour = now()->hour;

        // Unusual hours: 22:00 - 06:00
        if ($hour >= 22 || $hour < 6) {
            return 0.1;
        }

        return 0.0;
    }

    /**
     * Handle high-risk user (trigger cooldown)
     */
    private function handleHighRiskUser(
        User $user,
        CooldownService $cooldownService,
        AuditService $auditService,
        float $score,
    ): void {
        // Trigger cooldown
        $cooldownService->startCooldown(
            $user,
            \App\Enums\CooldownActionType::HUNTING_DETECTED,
            2, // 2 hours
            "High hunting score detected: {$score}"
        );

        // Log to audit
        $auditService->logEvent('hunting_high_risk', [
            'user_id' => $user->id,
            'tenant_id' => $this->tenantId,
            'score' => $score,
            'correlation_id' => $this->correlationId,
            'query_count' => count($this->queryData),
        ], 'security');

        // Log to security alert
        Log::channel('security_alert')->warning('High hunting risk detected', [
            'user_id' => $user->id,
            'tenant_id' => $this->tenantId,
            'score' => $score,
            'correlation_id' => $this->correlationId,
        ]);

        // Cache score for reference
        Cache::put("hunting_score:{$this->userId}", $score, self::CACHE_TTL_HOURS * 3600);
    }

    /**
     * Handle low-risk user (log for monitoring)
     */
    private function handleLowRiskUser(
        User $user,
        AuditService $auditService,
        float $score,
    ): void {
        $auditService->logEvent('hunting_low_risk', [
            'user_id' => $user->id,
            'tenant_id' => $this->tenantId,
            'score' => $score,
            'correlation_id' => $this->correlationId,
        ], 'security');

        // Cache score for reference
        Cache::put("hunting_score:{$this->userId}", $score, self::CACHE_TTL_HOURS * 3600);
    }
}
