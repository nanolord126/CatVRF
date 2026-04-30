<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Services;

use App\Domains\Advertising\Domain\Interfaces\AdShortRepositoryInterface;
use App\Domains\Advertising\Domain\Interfaces\AuctionRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

/**
 * Budget Pacing Service
 *
 * Implements budget pacing algorithms to optimize ad spend over campaign duration.
 * Ensures budget is distributed evenly or according to specified strategy to maximize impact.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class BudgetPacingService
{
    private const CACHE_TTL = 600; // 10 minutes

    public function __construct(
        private readonly AdShortRepositoryInterface $adShortRepository,
        private readonly AuctionRepositoryInterface $auctionRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Calculate recommended bid limit based on budget pacing
     *
     * @param int $adShortId The ad short ID
     * @param string $pacingStrategy Pacing strategy (even, accelerate, decelerate, front_load, back_load)
     * @return array{max_bid: int, remaining_budget: int, daily_budget: int, recommended_pacing: float}
     */
    public function calculateBidLimit(
        int $adShortId,
        string $pacingStrategy = 'even',
    ): array {
        $adShort = $this->adShortRepository->findById($adShortId);
        if ($adShort === null) {
            throw new \RuntimeException('Ad short not found');
        }

        $remainingBudget = $adShort->remainingBudget();
        $durationHours = $adShort->end_at->diffInHours($adShort->start_at);
        $elapsedHours = $adShort->start_at->diffInHours(now());
        $remainingHours = max(1, $durationHours - $elapsedHours);

        $totalHours = max(1, $durationHours);
        $hourlyBudget = $remainingBudget / $remainingHours;

        // Apply pacing strategy
        $pacingMultiplier = $this->getPacingMultiplier(
            strategy: $pacingStrategy,
            progress: $elapsedHours / $totalHours,
        );

        $recommendedHourlyBudget = (int) ($hourlyBudget * $pacingMultiplier);
        $recommendedDailyBudget = $recommendedHourlyBudget * 24;

        // Calculate recommended bid limit (conservative: 10% of hourly budget)
        $maxBid = (int) ($recommendedHourlyBudget * 0.1);

        $this->logger->info('Budget pacing calculated', [
            'ad_short_id' => $adShortId,
            'strategy' => $pacingStrategy,
            'remaining_budget' => $remainingBudget,
            'max_bid' => $maxBid,
        ]);

        return [
            'max_bid' => $maxBid,
            'remaining_budget' => $remainingBudget,
            'daily_budget' => $recommendedDailyBudget,
            'hourly_budget' => $recommendedHourlyBudget,
            'pacing_multiplier' => $pacingMultiplier,
            'progress' => $elapsedHours / $totalHours,
        ];
    }

    /**
     * Get pacing multiplier based on strategy and campaign progress
     */
    private function getPacingMultiplier(string $strategy, float $progress): float
    {
        return match ($strategy) {
            'even' => 1.0,
            'accelerate' => 1.0 + ($progress * 0.5), // Increase spend over time
            'decelerate' => 1.5 - ($progress * 0.5), // Decrease spend over time
            'front_load' => $progress < 0.5 ? 1.5 : 0.5, // Front-heavy
            'back_load' => $progress < 0.5 ? 0.5 : 1.5, // Back-heavy
            default => 1.0,
        };
    }

    /**
     * Check if campaign is pacing correctly
     *
     * @param int $adShortId The ad short ID
     * @return array{is_on_track: bool, variance_percent: float, recommendation: string}
     */
    public function checkPacingStatus(int $adShortId): array
    {
        $adShort = $this->adShortRepository->findById($adShortId);
        if ($adShort === null) {
            throw new \RuntimeException('Ad short not found');
        }

        $totalBudget = $adShort->budget;
        $spent = $adShort->spent;
        $durationHours = $adShort->end_at->diffInHours($adShort->start_at);
        $elapsedHours = $adShort->start_at->diffInHours(now());
        
        $progress = $elapsedHours / max(1, $durationHours);
        $expectedSpend = (int) ($totalBudget * $progress);
        $variance = $spent - $expectedSpend;
        $variancePercent = $totalBudget > 0 ? ($variance / $totalBudget) * 100 : 0;

        $isOnTrack = abs($variancePercent) < 10; // Within 10% is on track

        if ($variancePercent > 10) {
            $recommendation = 'Overspending: Consider reducing bid limits or pausing campaign';
        } elseif ($variancePercent < -10) {
            $recommendation = 'Underspending: Consider increasing bid limits to utilize budget';
        } else {
            $recommendation = 'On track: No action needed';
        }

        return [
            'is_on_track' => $isOnTrack,
            'variance_percent' => $variancePercent,
            'variance' => $variance,
            'expected_spend' => $expectedSpend,
            'actual_spend' => $spent,
            'progress' => $progress,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Get pacing recommendations for multiple campaigns
     *
     * @param array<int, int> $adShortIds Array of ad short IDs
     * @return array<int, array>
     */
    public function batchCheckPacing(array $adShortIds): array
    {
        $results = [];

        foreach ($adShortIds as $adShortId) {
            try {
                $results[$adShortId] = $this->checkPacingStatus($adShortId);
            } catch (\Throwable $e) {
                $this->logger->error('Pacing check failed', [
                    'ad_short_id' => $adShortId,
                    'error' => $e->getMessage(),
                ]);
                $results[$adShortId] = null;
            }
        }

        return $results;
    }

    /**
     * Auto-adjust bid limits based on pacing status
     *
     * @param int $adShortId The ad short ID
     * @param bool $dryRun If true, only return recommendation without applying
     * @return array{success: bool, new_max_bid: int|null, old_max_bid: int|null}
     */
    public function autoAdjustBidLimit(int $adShortId, bool $dryRun = true): array
    {
        $pacingStatus = $this->checkPacingStatus($adShortId);
        $bidLimit = $this->calculateBidLimit($adShortId, 'even');

        $adjustmentFactor = 1.0;

        if ($pacingStatus['variance_percent'] > 15) {
            $adjustmentFactor = 0.8; // Reduce by 20%
        } elseif ($pacingStatus['variance_percent'] > 10) {
            $adjustmentFactor = 0.9; // Reduce by 10%
        } elseif ($pacingStatus['variance_percent'] < -15) {
            $adjustmentFactor = 1.2; // Increase by 20%
        } elseif ($pacingStatus['variance_percent'] < -10) {
            $adjustmentFactor = 1.1; // Increase by 10%
        }

        $newMaxBid = (int) ($bidLimit['max_bid'] * $adjustmentFactor);

        $this->logger->info('Auto-adjusting bid limit', [
            'ad_short_id' => $adShortId,
            'dry_run' => $dryRun,
            'adjustment_factor' => $adjustmentFactor,
            'new_max_bid' => $newMaxBid,
            'variance_percent' => $pacingStatus['variance_percent'],
        ]);

        if (!$dryRun) {
            // Store adjusted bid limit in Redis for real-time enforcement
            Redis::setex(
                "pacing:bid_limit:{$adShortId}",
                self::CACHE_TTL,
                $newMaxBid
            );
        }

        return [
            'success' => true,
            'new_max_bid' => $newMaxBid,
            'old_max_bid' => $bidLimit['max_bid'],
            'adjustment_factor' => $adjustmentFactor,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Get budget pacing report for dashboard
     *
     * @param int $tenantId The tenant ID
     * @return array{campaigns: array, summary: array}
     */
    public function getPacingReport(int $tenantId): array
    {
        $adShorts = $this->adShortRepository->findByTenant($tenantId)
            ->where('status', 'active');

        $campaigns = [];
        $totalBudget = 0;
        $totalSpent = 0;
        $onTrackCount = 0;
        $overspendingCount = 0;
        $underspendingCount = 0;

        foreach ($adShorts as $adShort) {
            $pacing = $this->checkPacingStatus($adShort->id);
            
            $campaigns[] = [
                'id' => $adShort->uuid,
                'title' => $adShort->title,
                'budget' => $adShort->budget,
                'spent' => $adShort->spent,
                'variance_percent' => $pacing['variance_percent'],
                'is_on_track' => $pacing['is_on_track'],
                'recommendation' => $pacing['recommendation'],
            ];

            $totalBudget += $adShort->budget;
            $totalSpent += $adShort->spent;

            if ($pacing['is_on_track']) {
                $onTrackCount++;
            } elseif ($pacing['variance_percent'] > 0) {
                $overspendingCount++;
            } else {
                $underspendingCount++;
            }
        }

        return [
            'campaigns' => $campaigns,
            'summary' => [
                'total_campaigns' => count($campaigns),
                'total_budget' => $totalBudget,
                'total_spent' => $totalSpent,
                'overall_variance_percent' => $totalBudget > 0 
                    ? (($totalSpent - $totalBudget) / $totalBudget) * 100 
                    : 0,
                'on_track_count' => $onTrackCount,
                'overspending_count' => $overspendingCount,
                'underspending_count' => $underspendingCount,
            ],
        ];
    }
}
