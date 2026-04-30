<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\ValueObjects;

final readonly class TimeSlot
{
    public function __construct(
        public \DateTimeImmutable $startTime,
        public \DateTimeImmutable $endTime,
    ) {
        if ($endTime <= $startTime) {
            throw new \InvalidArgumentException('End time must be after start time');
        }
    }

    public static function fromStrings(string $startTime, string $endTime): self
    {
        return new self(
            new \DateTimeImmutable($startTime),
            new \DateTimeImmutable($endTime),
        );
    }

    public function getDuration(): \DateInterval
    {
        return $startTime->diff($this->endTime);
    }

    public function getDurationMinutes(): int
    {
        return (int) $this->startTime->diff($this->endTime)->format('%i') + 
               (int) $this->startTime->diff($this->endTime)->format('%h') * 60;
    }

    public function overlaps(TimeSlot $other): bool
    {
        return $this->startTime < $other->endTime && $other->startTime < $this->endTime;
    }

    public function contains(\DateTimeImmutable $dateTime): bool
    {
        return $dateTime >= $this->startTime && $dateTime < $this->endTime;
    }

    public function isPast(): bool
    {
        return $this->endTime < new \DateTimeImmutable();
    }

    public function isFuture(): bool
    {
        return $this->startTime > new \DateTimeImmutable();
    }

    public function isCurrent(): bool
    {
        $now = new \DateTimeImmutable();
        return $this->contains($now);
    }

    public function toArray(): array
    {
        return [
            'start_time' => $this->startTime->format('Y-m-d H:i:s'),
            'end_time' => $this->endTime->format('Y-m-d H:i:s'),
            'duration_minutes' => $this->getDurationMinutes(),
        ];
    }
}
