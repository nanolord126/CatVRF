<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\ValueObjects;

final readonly class RideStatus
{
    public const REQUESTED = 'requested';
    public const ACCEPTED = 'accepted';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';
    public const NO_SHOW = 'no_show';

    private const VALID_STATUSES = [
        self::REQUESTED,
        self::ACCEPTED,
        self::IN_PROGRESS,
        self::COMPLETED,
        self::CANCELLED,
        self::NO_SHOW,
    ];

    public function __construct(
        public string $value,
    ) {
        if (!in_array($this->value, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid ride status: ' . $this->value);
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function requested(): self
    {
        return new self(self::REQUESTED);
    }

    public static function accepted(): self
    {
        return new self(self::ACCEPTED);
    }

    public static function inProgress(): self
    {
        return new self(self::IN_PROGRESS);
    }

    public static function completed(): self
    {
        return new self(self::COMPLETED);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->value, [self::REQUESTED, self::ACCEPTED], true);
    }

    public function isFinal(): bool
    {
        return in_array($this->value, [self::COMPLETED, self::CANCELLED, self::NO_SHOW], true);
    }
}
