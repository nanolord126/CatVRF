<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\ValueObjects;

use Carbon\Carbon;
use Carbon\CarbonInterval;

final readonly class Period
{
    private function __construct(
        public Carbon $startDate,
        public Carbon $endDate,
    ) {
        if ($this->endDate->lt($this->startDate)) {
            throw new \InvalidArgumentException('End date must be after start date');
        }
    }

    public static function create(Carbon $startDate, Carbon $endDate): self
    {
        return new self(
            startDate: $startDate,
            endDate: $endDate,
        );
    }

    public static function fromDays(int $days): self
    {
        return new self(
            startDate: now(),
            endDate: now()->addDays($days),
        );
    }

    public static function fromWeeks(int $weeks): self
    {
        return new self(
            startDate: now(),
            endDate: now()->addWeeks($weeks),
        );
    }

    public static function fromMonths(int $months): self
    {
        return new self(
            startDate: now(),
            endDate: now()->addMonths($months),
        );
    }

    public function duration(): CarbonInterval
    {
        return $this->startDate->diffAsCarbonInterval($this->endDate);
    }

    public function durationInDays(): int
    {
        return (int) $this->startDate->diffInDays($this->endDate);
    }

    public function durationInHours(): int
    {
        return (int) $this->startDate->diffInHours($this->endDate);
    }

    public function contains(Carbon $date): bool
    {
        return $date->between($this->startDate, $this->endDate);
    }

    public function overlaps(Period $other): bool
    {
        return $this->startDate->lt($other->endDate) && $this->endDate->gt($other->startDate);
    }

    public function isExpired(): bool
    {
        return $this->endDate->lt(now());
    }

    public function isFuture(): bool
    {
        return $this->startDate->gt(now());
    }

    public function isCurrent(): bool
    {
        return $this->contains(now());
    }

    public function extend(int $days): self
    {
        return new self(
            startDate: $this->startDate,
            endDate: $this->endDate->addDays($days),
        );
    }

    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate->toIso8601String(),
            'end_date' => $this->endDate->toIso8601String(),
            'duration_days' => $this->durationInDays(),
            'duration_hours' => $this->durationInHours(),
            'is_expired' => $this->isExpired(),
            'is_future' => $this->isFuture(),
            'is_current' => $this->isCurrent(),
        ];
    }
}
