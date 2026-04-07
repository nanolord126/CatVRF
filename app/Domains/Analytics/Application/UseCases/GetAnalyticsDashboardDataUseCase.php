<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Application\UseCases;

use App\Domains\Analytics\Domain\Interfaces\AnalyticsEventRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class GetAnalyticsDashboardDataUseCase
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Analytics\Application\UseCases
 */
final readonly class GetAnalyticsDashboardDataUseCase
{
    public function __construct(
        private AnalyticsEventRepositoryInterface $repository
    ) {
}

    /**
     * Handle execute operation.
     *
     * @throws \DomainException
     */
    public function execute(int $tenantId, string $metric, \DateTime $from, \DateTime $to, string $groupBy): Collection
    {
        return $this->repository->getAggregatedData($tenantId, $metric, $from, $to, $groupBy);
    }
}
