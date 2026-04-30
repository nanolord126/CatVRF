<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\ValueObjects;

use Carbon\Carbon;

final readonly class DeliverySlot
{
    private function __construct(
        public Carbon $date,
        public string $timeRange,
        public Money $cost,
    ) {}

    public static function create(Carbon $date, string $timeRange, Money $cost): self
    {
        return new self(
            date: $date,
            timeRange: $timeRange,
            cost: $cost,
        );
    }

    public static function fromString(string $slotString, Money $cost = null): self
    {
        // Format: "2026-04-27 10:00-12:00"
        preg_match('/(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}-\d{2}:\d{2})/', $slotString, $matches);

        if (count($matches) !== 3) {
            throw new \InvalidArgumentException('Invalid delivery slot format. Expected: "YYYY-MM-DD HH:MM-HH:MM"');
        }

        return new self(
            date: Carbon::parse($matches[1]),
            timeRange: $matches[2],
            cost: $cost ?? Money::zero(),
        );
    }

    public function isAvailable(): bool
    {
        return $this->date->isFuture();
    }

    public function isToday(): bool
    {
        return $this->date->isToday();
    }

    public function isTomorrow(): bool
    {
        return $this->date->isTomorrow();
    }

    public function getStartTime(): Carbon
    {
        [$start, $end] = explode('-', $this->timeRange);
        return $this->date->copy()->setTimeFromTimeString($start);
    }

    public function getEndTime(): Carbon
    {
        [$start, $end] = explode('-', $this->timeRange);
        return $this->date->copy()->setTimeFromTimeString($end);
    }

    public function getDurationInHours(): int
    {
        $start = $this->getStartTime();
        $end = $this->getEndTime();
        return $start->diffInHours($end);
    }

    public function isMorning(): bool
    {
        return $this->getStartTime()->hour < 12;
    }

    public function isAfternoon(): bool
    {
        $hour = $this->getStartTime()->hour;
        return $hour >= 12 && $hour < 17;
    }

    public function isEvening(): bool
    {
        return $this->getStartTime()->hour >= 17;
    }

    public function format(): string
    {
        return $this->date->format('d.m.Y') . ' ' . $this->timeRange;
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date->toIso8601String(),
            'time_range' => $this->timeRange,
            'cost' => $this->cost->toArray(),
            'start_time' => $this->getStartTime()->toIso8601String(),
            'end_time' => $this->getEndTime()->toIso8601String(),
            'duration_hours' => $this->getDurationInHours(),
        ];
    }
}
