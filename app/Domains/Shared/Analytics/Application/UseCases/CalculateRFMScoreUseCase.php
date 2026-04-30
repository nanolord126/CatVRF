<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\UseCases;

use Modules\Analytics\Domain\Events\RFMScoreCalculated;
use Modules\Analytics\Domain\Repositories\BehavioralEventRepositoryInterface;
use Modules\Analytics\Domain\ValueObjects\Timestamp;
use Modules\Analytics\Domain\ValueObjects\UserId;
use Illuminate\Support\Facades\Event as LaravelEvent;

final readonly class CalculateRFMScoreUseCase
{
    public function __construct(
        private BehavioralEventRepositoryInterface $repository,
    ) {
    }

    public function execute(int $userId): array
    {
        $userIdVo = new UserId($userId);
        $now = Timestamp::now();

        // Calculate Recency (days since last purchase)
        $lastPurchase = $this->repository->findLastPurchase($userIdVo);
        $recencyDays = $lastPurchase
            ? $now->value->diffInDays($lastPurchase->timestamp->value)
            : 365; // Default to 1 year if no purchase
        $recencyScore = $this->calculateRecencyScore($recencyDays);

        // Calculate Frequency (number of purchases in last 90 days)
        $purchaseCount = $this->repository->countPurchases($userIdVo, 90);
        $frequencyScore = $this->calculateFrequencyScore($purchaseCount);

        // Calculate Monetary (total spend in last 90 days)
        $totalSpend = $this->repository->totalSpend($userIdVo, 90);
        $monetaryScore = $this->calculateMonetaryScore($totalSpend);

        // Calculate overall RFM score
        $overallScore = ($recencyScore + $frequencyScore + $monetaryScore) / 3;

        // Dispatch domain event
        LaravelEvent::dispatch(new RFMScoreCalculated(
            userId: $userIdVo,
            recencyScore: $recencyScore,
            frequencyScore: $frequencyScore,
            monetaryScore: $monetaryScore,
            overallScore: $overallScore,
            calculatedAt: $now,
        ));

        return [
            'recency_score' => $recencyScore,
            'frequency_score' => $frequencyScore,
            'monetary_score' => $monetaryScore,
            'overall_score' => $overallScore,
            'calculated_at' => $now->value->toIso8601String(),
        ];
    }

    private function calculateRecencyScore(int $daysSinceLastPurchase): int
    {
        // Higher score for more recent purchases (1-100)
        if ($daysSinceLastPurchase <= 7) return 100;
        if ($daysSinceLastPurchase <= 14) return 80;
        if ($daysSinceLastPurchase <= 30) return 60;
        if ($daysSinceLastPurchase <= 60) return 40;
        if ($daysSinceLastPurchase <= 90) return 20;
        return 10;
    }

    private function calculateFrequencyScore(int $purchaseCount): int
    {
        // Higher score for more frequent purchases (1-100)
        if ($purchaseCount >= 10) return 100;
        if ($purchaseCount >= 7) return 80;
        if ($purchaseCount >= 5) return 60;
        if ($purchaseCount >= 3) return 40;
        if ($purchaseCount >= 1) return 20;
        return 10;
    }

    private function calculateMonetaryScore(float $totalSpend): int
    {
        // Higher score for higher spend (1-100)
        if ($totalSpend >= 10000) return 100;
        if ($totalSpend >= 5000) return 80;
        if ($totalSpend >= 1000) return 60;
        if ($totalSpend >= 500) return 40;
        if ($totalSpend >= 100) return 20;
        return 10;
    }
}
