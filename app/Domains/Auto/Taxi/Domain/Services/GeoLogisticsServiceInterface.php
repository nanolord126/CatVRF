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


namespace App\Domains\Auto\Taxi\Domain\Services;

use App\Domains\Auto\Taxi\Domain\ValueObjects\Coordinate;

interface GeoLogisticsServiceInterface
{
    /**
     * Рассчитать стоимость поездки в копейках.
     */
    public function calculatePrice(Coordinate $from, Coordinate $to): int;

    /**
     * Рассчитать время поездки в минутах.
     */
    public function estimateDuration(Coordinate $from, Coordinate $to): int;

    /**
     * Вернуть маршрут с ключами start, end, distance_km, duration_minutes.
     */
    public function calculateRoute(Coordinate $from, Coordinate $to): array;
}
