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

use App\Domains\GeoLogistics\Domain\Models\Shipment;

/**
 * Интерфейс репозитория для изоляции логики хранения Shipment.
 */
interface ShipmentRepositoryInterface
{
    public function findById(int $id): ?Shipment;

    public function save(Shipment $shipment): void;
}
