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


namespace App\Domains\Auto\Taxi\Domain\Repository;

use App\Domains\Auto\Taxi\Domain\Entities\TaxiFleet;
use App\Domains\Auto\Taxi\Domain\ValueObjects\TaxiFleetId;

interface TaxiFleetRepositoryInterface
{
    public function findById(TaxiFleetId $id): ?TaxiFleet;

    public function save(TaxiFleet $fleet): void;
}
