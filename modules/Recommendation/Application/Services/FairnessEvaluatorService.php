<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Services;

use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Domain\Interfaces\FairnessEvaluatorInterface;
use Modules\Recommendation\Domain\ValueObjects\FairnessConfig;

final readonly class FairnessEvaluatorService implements FairnessEvaluatorInterface
{
    public function evaluateFeedFairness(int $tenantId, array $recommendedItems, FairnessConfig $config): array
    {
        $sellerDistribution = $this->getSellerExposureDistribution($tenantId, $recommendedItems);
        $totalItems = count($recommendedItems);

        if ($totalItems === 0) {
            return [
                'is_fair' => true,
                'needs_rebalance' => false,
                'distribution' => [],
                'issues' => [],
            ];
        }

        $issues = [];
        $needsRebalance = false;

        $sellerCounts = array_count_values(array_column($recommendedItems, 'seller_id'));
        $maxSellerCount = max($sellerCounts);
        $maxSellerShare = $maxSellerCount / $totalItems;

        if ($maxSellerShare > $config->getMaxDominanceShare()) {
            $issues[] = "Seller dominance: {$maxSellerShare} exceeds max {$config->getMaxDominanceShare()}";
            $needsRebalance = true;
        }

        $uniqueSellers = count($sellerCounts);
        if ($uniqueSellers < $config->getMinSellersInFeed()) {
            $issues[] = "Seller diversity: {$uniqueSellers} sellers below min {$config->getMinSellersInFeed()}";
            $needsRebalance = true;
        }

        foreach ($sellerCounts as $sellerId => $count) {
            $share = $count / $totalItems;
            if ($share < $config->getMinSellerExposure()) {
                $issues[] = "Seller {$sellerId} exposure: {$share} below min {$config->getMinSellerExposure()}";
            }
        }

        $verticalDistribution = array_count_values(array_column($recommendedItems, 'vertical'));
        $verticalCounts = array_values($verticalDistribution);
        if (count($verticalCounts) > 1) {
            $maxVerticalShare = max($verticalCounts) / $totalItems;
            if ($maxVerticalShare > (1.0 - $config->getDiversityMinGap())) {
                $issues[] = "Vertical dominance: {$maxVerticalShare} exceeds diversity threshold";
                $needsRebalance = true;
            }
        }

        return [
            'is_fair' => !$needsRebalance,
            'needs_rebalance' => $needsRebalance,
            'distribution' => $sellerDistribution,
            'issues' => $issues,
            'metrics' => [
                'unique_sellers' => $uniqueSellers,
                'max_seller_share' => $maxSellerShare,
                'total_items' => $totalItems,
                'seller_counts' => $sellerCounts,
            ],
        ];
    }

    public function getSellerExposureDistribution(int $tenantId, array $recommendedItems): array
    {
        $distribution = [];
        $totalItems = count($recommendedItems);

        if ($totalItems === 0) {
            return $distribution;
        }

        $sellerCounts = array_count_values(array_column($recommendedItems, 'seller_id'));

        foreach ($sellerCounts as $sellerId => $count) {
            $distribution[$sellerId] = [
                'count' => $count,
                'share' => $count / $totalItems,
                'items' => array_values(array_filter(
                    $recommendedItems,
                    fn($item) => $item['seller_id'] === $sellerId
                )),
            ];
        }

        uasort($distribution, fn($a, $b) => $b['share'] <=> $a['share']);

        return $distribution;
    }

    public function rebalanceForFairness(int $tenantId, array $rankedItems, FairnessConfig $config): array
    {
        if (count($rankedItems) === 0) {
            return [];
        }

        $sellerCounts = array_fill_keys(
            array_unique(array_column($rankedItems, 'seller_id')),
            0
        );

        $minExposureCount = max(1, (int) floor(count($rankedItems) * $config->getMinSellerExposure()));
        $maxDominanceCount = (int) ceil(count($rankedItems) * $config->getMaxDominanceShare());

        $rebalanced = [];
        $overflowItems = [];
        $sellerSlots = [];

        foreach ($rankedItems as $item) {
            $sellerId = $item['seller_id'];

            if (!isset($sellerSlots[$sellerId])) {
                $sellerSlots[$sellerId] = [
                    'count' => 0,
                    'items' => [],
                ];
            }

            if ($sellerSlots[$sellerId]['count'] < $minExposureCount) {
                $sellerSlots[$sellerId]['items'][] = $item;
                $sellerSlots[$sellerId]['count']++;
            } elseif ($sellerSlots[$sellerId]['count'] < $maxDominanceCount) {
                $overflowItems[] = $item;
            }
        }

        foreach ($sellerSlots as $sellerId => $slot) {
            foreach ($slot['items'] as $item) {
                $rebalanced[] = $item;
            }
        }

        usort($overflowItems, fn($a, $b) => $b['score'] <=> $a['score']);

        $currentSellerCounts = array_count_values(array_column($rebalanced, 'seller_id'));

        foreach ($overflowItems as $item) {
            $sellerId = $item['seller_id'];
            $currentCount = $currentSellerCounts[$sellerId] ?? 0;

            if ($currentCount < $maxDominanceCount) {
                $rebalanced[] = $item;
                $currentSellerCounts[$sellerId] = $currentCount + 1;
            }
        }

        $explorationBudget = (int) floor(count($rebalanced) * $config->getExplorationBudget());
        if ($explorationBudget > 0) {
            $lowExposureSellers = array_filter(
                $currentSellerCounts,
                fn($count) => $count < $minExposureCount * 2
            );

            if (count($lowExposureSellers) > 0) {
                $candidates = array_filter(
                    $overflowItems,
                    fn($item) => isset($lowExposureSellers[$item['seller_id']])
                );

                $explorationItems = array_slice($candidates, 0, $explorationBudget);

                foreach ($explorationItems as $item) {
                    if (!in_array($item, $rebalanced, true)) {
                        $rebalanced[] = $item;
                    }
                }
            }
        }

        usort($rebalanced, function($a, $b) use ($currentSellerCounts) {
            $aCount = $currentSellerCounts[$a['seller_id']] ?? 0;
            $bCount = $currentSellerCounts[$b['seller_id']] ?? 0;

            if ($aCount === $bCount) {
                return $b['score'] <=> $a['score'];
            }

            return $aCount <=> $bCount;
        });

        foreach ($rebalanced as $idx => &$item) {
            $item['position'] = $idx;
        }

        Log::info('Recommendations rebalanced for fairness', [
            'tenant_id' => $tenantId,
            'original_count' => count($rankedItems),
            'rebalanced_count' => count($rebalanced),
            'config' => $config->toArray(),
        ]);

        return array_values($rebalanced);
    }

    public function calculateDiversityScore(array $recommendedItems): float
    {
        if (count($recommendedItems) === 0) {
            return 0.0;
        }

        $sellers = array_unique(array_column($recommendedItems, 'seller_id'));
        $verticals = array_unique(array_column($recommendedItems, 'vertical'));
        $totalItems = count($recommendedItems);

        $sellerEntropy = $this->calculateEntropy(
            array_count_values(array_column($recommendedItems, 'seller_id')),
            $totalItems
        );

        $verticalEntropy = $this->calculateEntropy(
            array_count_values(array_column($recommendedItems, 'vertical')),
            $totalItems
        );

        $sellerDiversity = count($sellers) / $totalItems;
        $verticalDiversity = count($verticals) / max(1, count(array_unique(array_column($recommendedItems, 'vertical'))));

        return ($sellerEntropy * 0.4) + ($verticalEntropy * 0.3) + ($sellerDiversity * 0.2) + ($verticalDiversity * 0.1);
    }

    private function calculateEntropy(array $counts, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        $entropy = 0.0;
        foreach ($counts as $count) {
            if ($count > 0) {
                $probability = $count / $total;
                $entropy -= $probability * log($probability);
            }
        }

        return $entropy / log(max(2, count($counts)));
    }
}
