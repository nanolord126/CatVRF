<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/component
 */

namespace App\Domains\Advertising\Domain\Interfaces;

use App\Models\User;
use Illuminate\Support\Collection;

interface AdPlacementStrategyInterface
{
    /**
     * Select the best ad placement from a collection of campaigns.
     *
     * @return int|null The ID of the selected AdCampaign
     */
    public function selectBestPlacement(Collection $campaigns, User $user, string $placementZone): ?int;
}
