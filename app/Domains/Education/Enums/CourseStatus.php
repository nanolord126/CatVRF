<?php

declare(strict_types=1);

namespace App\Domains\Education\Enums;

/**
 * КАНОН 2026: CourseStatus (Education).
 * Статусы курса/услуги.
 */
enum CourseStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';

    public function isPublished(): bool
    {
        return $this === self::PUBLISHED;
    }
}
