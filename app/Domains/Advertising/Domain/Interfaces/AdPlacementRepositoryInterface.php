<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\Advertising\Domain\Interfaces;

use App\Domains\Advertising\Domain\Entities\AdPlacement;
use Illuminate\Support\Collection;

interface AdPlacementRepositoryInterface
{
    public function findById(int $id): ?AdPlacement;
    public function findByCampaign(int $campaignId): Collection;
    public function save(AdPlacement $placement): AdPlacement;
}
