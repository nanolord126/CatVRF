<?php

declare(strict_types=1);

/**
 * UpdateShipmentStatusDto — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/updateshipmentstatusdto
 */


namespace App\Domains\GeoLogistics\Application\DTOs;

use App\Domains\GeoLogistics\Domain\Enums\ShipmentStatus;

/**
 * Class UpdateShipmentStatusDto
 *
 * Part of the GeoLogistics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\GeoLogistics\Application\DTOs
 */
final readonly class UpdateShipmentStatusDto
{
    public function __construct(
        public int $shipmentId,
        public ShipmentStatus $newStatus,
        public string $correlationId) {}
/**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
