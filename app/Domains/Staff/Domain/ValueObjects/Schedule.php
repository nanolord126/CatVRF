<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\ValueObjects;

use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

/**
 * Class Schedule
 *
 * Part of the Staff vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Staff\Domain\ValueObjects
 */
final readonly class Schedule
{
    /**
     * @param Collection<int, ScheduleTimeSlot> $timeSlots
     */
    public function __construct(
        public Collection $timeSlots
    ) {
        Assert::allIsInstanceOf($timeSlots, ScheduleTimeSlot::class);
    }

    /**
     * Handle isAvailable operation.
     *
     * @throws \DomainException
     */
    public function isAvailable(\DateTimeImmutable $dateTime): bool
    {
        foreach ($this->timeSlots as $slot) {
            if ($slot->contains($dateTime)) {
                return true;
            }
        }
        return false;
    }
}
