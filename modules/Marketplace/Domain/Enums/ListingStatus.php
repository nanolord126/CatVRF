<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Enums;

/**
 * Статусы витринной позиции маркетплейса
 */
enum ListingStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PUBLISHED = 'published';
    case SUSPENDED = 'suspended';
    case ARCHIVED = 'archived';
    case EXPIRED = 'expired';
    case OUT_OF_STOCK = 'out_of_stock';

    public function canBePublished(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING, self::SUSPENDED], true);
    }

    public function canBeSuspended(): bool
    {
        return in_array($this, [self::PUBLISHED, self::PENDING], true);
    }

    public function canBeArchived(): bool
    {
        return in_array($this, [self::PUBLISHED, self::SUSPENDED, self::OUT_OF_STOCK], true);
    }

    public function isVisible(): bool
    {
        return in_array($this, [self::PUBLISHED], true);
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING], true);
    }

    public function isTransitionAllowedTo(self $target): bool
    {
        $transitions = [
            self::DRAFT->value => [self::PENDING->value, self::ARCHIVED->value],
            self::PENDING->value => [self::PUBLISHED->value, self::SUSPENDED->value, self::ARCHIVED->value],
            self::PUBLISHED->value => [self::SUSPENDED->value, self::OUT_OF_STOCK->value, self::EXPIRED->value, self::ARCHIVED->value],
            self::SUSPENDED->value => [self::PUBLISHED->value, self::ARCHIVED->value],
            self::OUT_OF_STOCK->value => [self::PUBLISHED->value, self::ARCHIVED->value],
            self::EXPIRED->value => [self::ARCHIVED->value],
            self::ARCHIVED->value => [],
        ];

        return in_array($target->value, $transitions[$this->value] ?? [], true);
    }
}
