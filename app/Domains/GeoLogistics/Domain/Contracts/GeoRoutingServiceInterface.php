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

 */


namespace App\Domains\GeoLogistics\Domain\Contracts;

use App\Domains\GeoLogistics\Domain\ValueObjects\Coordinates;

/**
 * Антикоррупционный слой. Интерфейс для взаимодействия с внешними провайдерами карт.
 */
interface GeoRoutingServiceInterface
{
    /**
     * @return array{distance_meters: int, duration_seconds: int}
     */
    public function calculateRouteMode(Coordinates $origin, Coordinates $destination, string $mode = 'driving'): array;
}
