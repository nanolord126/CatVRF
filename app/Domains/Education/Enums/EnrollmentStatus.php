<?php

declare(strict_types=1);

namespace App\Domains\Education\Enums;

/**
 * КАНОН 2026: EnrollmentStatus (Education).
 * Перечисление статусов зачисления студента на курс.
 */
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
