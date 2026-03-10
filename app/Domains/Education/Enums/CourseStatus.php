<?php

namespace App\Domains\Education\Enums;

enum CourseStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case STARTED = 'started';
    case COMPLETED = 'completed';
    case ARCHIVED = 'archived';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Черновик',
            self::PUBLISHED => 'Опубликовано',
            self::STARTED => 'Начато',
            self::COMPLETED => 'Завершено',
            self::ARCHIVED => 'В архиве',
            self::CANCELLED => 'Отменено',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PUBLISHED, self::STARTED]);
    }

    public function canEnroll(): bool
    {
        return in_array($this, [self::PUBLISHED, self::STARTED]);
    }
}
