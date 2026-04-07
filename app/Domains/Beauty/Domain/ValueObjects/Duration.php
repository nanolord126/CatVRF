<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Duration
{
    /**
     * @param int $minutes
     */
    private function __construct(private int $minutes)
    {
        $this->ensureIsPositive($minutes);
    }

    /**
     * @param int $minutes
     * @return void
     */
    private function ensureIsPositive(int $minutes): void
    {
        if ($minutes <= 0) {
            throw new InvalidArgumentException('Duration must be positive.');
        }
    }

    /**
     * @param int $minutes
     * @return static
     */
    public static function fromMinutes(int $minutes): self
    {
        return new self($minutes);
    }

    /**
     * @return int
     */
    public function getMinutes(): int
    {
        return $this->minutes;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->minutes === $other->minutes;
    }

    /**
     * @return string
     */
    public function getFormatted(): string
    {
        $hours = floor($this->minutes / 60);
        $remainingMinutes = $this->minutes % 60;

        $result = '';
        if ($hours > 0) {
            $result .= $hours . ' ч ';
        }
        if ($remainingMinutes > 0) {
            $result .= $remainingMinutes . ' мин';
        }

        return trim($result);
    }
}
