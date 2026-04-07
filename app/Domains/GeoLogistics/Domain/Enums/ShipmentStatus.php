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


namespace App\Domains\GeoLogistics\Domain\Enums;

/**
 * Исключительный перечень статусов доставки.
 */
enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case PICKED_UP = 'picked_up';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
}
