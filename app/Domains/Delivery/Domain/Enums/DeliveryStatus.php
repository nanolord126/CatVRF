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


namespace App\Domains\Delivery\Domain\Enums;

enum DeliveryStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case IN_PROGRESS = 'in_progress';
    case DELIVERED = 'delivered';
    case CANCELED = 'canceled';
    case FAILED = 'failed';
}
