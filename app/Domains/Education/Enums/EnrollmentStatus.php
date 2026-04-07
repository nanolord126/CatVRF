<?php declare(strict_types=1);

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


namespace App\Domains\Education\Enums;

enum EnrollmentStatus: string
{

    case PENDING = 'pending';
        case ACTIVE = 'active';
        case COMPLETED = 'completed';
        case REJECTED = 'rejected';
        case REFUNDED = 'refunded';

        public function canAccessLessons(): bool
        {
            return $this === self::ACTIVE || $this === self::COMPLETED;
        }
}
