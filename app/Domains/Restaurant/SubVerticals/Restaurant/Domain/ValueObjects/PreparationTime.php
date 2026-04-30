<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class PreparationTime
{
    public function __construct(
        public int $minutes,
    ) {
        if ($minutes < 0) {
            throw new InvalidArgumentException('Preparation time cannot be negative');
        }

        if ($minutes > 480) { // 8 hours max
            throw new InvalidArgumentException('Preparation time cannot exceed 8 hours');
        }
    }

    public function inSeconds(): int
    {
        return $this->minutes * 60;
    }

    public function inMilliseconds(): int
    {
        return $this->minutes * 60 * 1000;
    }

    public function isOverdue(int $elapsedMinutes): bool
    {
        return $elapsedMinutes > $this->minutes;
    }

    public function getRemainingMinutes(int $elapsedMinutes): int
    {
        $remaining = $this->minutes - $elapsedMinutes;
        return max(0, $remaining);
    }

    public function getProgressPercentage(int $elapsedMinutes): float
    {
        if ($this->minutes === 0) {
            return 100.0;
        }

        $percentage = ($elapsedMinutes / $this->minutes) * 100;
        return min(100.0, max(0.0, $percentage));
    }
}
