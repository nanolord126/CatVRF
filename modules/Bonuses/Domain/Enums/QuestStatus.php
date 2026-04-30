<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Enums;

/**
 * Enum QuestStatus
 *
 * Defines the status of a user's quest progress.
 */
enum QuestStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CLAIMED = 'claimed';
    case EXPIRED = 'expired';

    /**
     * Checks if the quest is still active.
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::IN_PROGRESS, self::COMPLETED => true,
            self::CLAIMED, self::EXPIRED => false,
        };
    }

    /**
     * Checks if rewards can be claimed.
     */
    public function canClaim(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Checks if rewards have been claimed.
     */
    public function isClaimed(): bool
    {
        return $this === self::CLAIMED;
    }

    /**
     * Creates quest status from string.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::IN_PROGRESS;
    }
}
