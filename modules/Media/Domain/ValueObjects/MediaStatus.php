<?php

declare(strict_types=1);

namespace Modules\Media\Domain\ValueObjects;

enum MediaStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case ACTIVE = 'active';
    case OPTIMIZED = 'optimized';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';

    public function canBeAccessed(): bool
    {
        return in_array($this, [self::ACTIVE, self::OPTIMIZED], true);
    }

    public function isProcessing(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING], true);
    }
}
