<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\ValueObjects;

final readonly class ServiceDuration
{
    public function __construct(
        public int $durationMinutes,
        public int $bufferMinutes = 0,
    ) {
        if ($durationMinutes <= 0) {
            throw new \InvalidArgumentException('Duration must be positive');
        }
        if ($bufferMinutes < 0) {
            throw new \InvalidArgumentException('Buffer cannot be negative');
        }
    }

    public static function fromMinutes(int $durationMinutes, int $bufferMinutes = 0): self
    {
        return new self($durationMinutes, $bufferMinutes);
    }

    public function getTotalMinutes(): int
    {
        return $this->durationMinutes + $this->bufferMinutes;
    }

    public function getEndTime(\DateTimeImmutable $startTime): \DateTimeImmutable
    {
        return $startTime->modify("+{$this->getTotalMinutes()} minutes");
    }

    public function format(): string
    {
        $hours = intdiv($this->durationMinutes, 60);
        $minutes = $this->durationMinutes % 60;
        
        if ($hours > 0) {
            return sprintf('%dч %dмин', $hours, $minutes);
        }
        return sprintf('%dмин', $minutes);
    }

    public function toArray(): array
    {
        return [
            'duration_minutes' => $this->durationMinutes,
            'buffer_minutes' => $this->bufferMinutes,
            'total_minutes' => $this->getTotalMinutes(),
        ];
    }
}
