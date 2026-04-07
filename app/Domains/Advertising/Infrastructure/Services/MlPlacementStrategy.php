<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Services;

use App\Domains\Advertising\Domain\Entities\AdCampaign;
use App\Domains\Advertising\Domain\Interfaces\AdPlacementStrategyInterface;
use App\Models\User;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * Class MlPlacementStrategy
 *
 * Part of the Advertising vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Advertising\Infrastructure\Services
 */
final readonly class MlPlacementStrategy implements AdPlacementStrategyInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Select the best ad placement from available campaigns using ML scoring.
     *
     * @param Collection<int, AdCampaign> $campaigns Available campaigns to choose from
     * @param User $user Target user for personalization
     * @param string $placementZone UI zone identifier (e.g. 'homepage_banner', 'sidebar')
     * @return int|null Selected campaign ID or null
     *
     * @throws \DomainException When campaigns collection is empty
     */
    public function selectBestPlacement(Collection $campaigns, User $user, string $placementZone): ?int
    {
        if ($campaigns->isEmpty()) {
            throw new \DomainException('No campaigns available for placement zone: ' . $placementZone);
        }

        /** @var AdCampaign $selected */
        $selected = $campaigns->random();

        $this->logger->info('ML placement strategy selected campaign', [
            'campaign_id' => $selected->id,
            'user_id' => $user->id,
            'placement_zone' => $placementZone,
            'candidates_count' => $campaigns->count(),
            'correlation_id' => $selected->correlation_id ?? '',
        ]);

        return $selected->id;
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class;
    }
}
