<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Interfaces;

use Modules\Recommendation\Domain\ValueObjects\FairnessConfig;

interface FairnessEvaluatorInterface
{
    public function evaluateFeedFairness(int $tenantId, array $recommendedItems, FairnessConfig $config): array;

    public function getSellerExposureDistribution(int $tenantId, array $recommendedItems): array;

    public function rebalanceForFairness(int $tenantId, array $rankedItems, FairnessConfig $config): array;
}
