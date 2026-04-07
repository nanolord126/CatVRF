<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\ValueObjects;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class Schedule
{
    /**
     * @var array<int, array<string, string>>
     */
    private array $weeklySchedule;

    /**
     * @param array<int, array<string, string>> $weeklySchedule
     */
    public function __construct(array $weeklySchedule)
    {
        $this->validateSchedule($weeklySchedule);
        $this->weeklySchedule = $weeklySchedule;
    }

    /**
     * @param array<int, array<string, string>> $schedule
     * @return void
     */
    private function validateSchedule(array $schedule): void
    {
        foreach ($schedule as $dayOfWeek => $times) {
            if ($dayOfWeek < 1 || $dayOfWeek > 7) {
                throw new InvalidArgumentException("Invalid day of week: {$dayOfWeek}.");
            }
            if (!isset($times['start']) || !isset($times['end'])) {
                throw new InvalidArgumentException("Start and end times are required for day {$dayOfWeek}.");
            }
            if (strtotime($times['start']) >= strtotime($times['end'])) {
                throw new InvalidArgumentException("Start time must be before end time for day {$dayOfWeek}.");
            }
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getWeeklySchedule(): array
    {
        return $this->weeklySchedule;
    }

    /**
     * @param CarbonImmutable $dateTime
     * @return bool
     */
    public function isOpenAt(CarbonImmutable $dateTime): bool
    {
        $dayOfWeek = $dateTime->dayOfWeekIso;
        if (!isset($this->weeklySchedule[$dayOfWeek])) {
            return false;
        }

        $scheduleForDay = $this->weeklySchedule[$dayOfWeek];
        $time = $dateTime->format('H:i:s');

        return $time >= $scheduleForDay['start'] && $time <= $scheduleForDay['end'];
    }

    /**
     * @param CarbonImmutable $date
     * @return array<string>
     */
    public function getAvailableSlotsForDate(CarbonImmutable $date, Duration $serviceDuration, array $bookedSlots): array
    {
        $dayOfWeek = $date->dayOfWeekIso;
        if (!isset($this->weeklySchedule[$dayOfWeek])) {
            return [];
        }

        $scheduleForDay = $this->weeklySchedule[$dayOfWeek];
        $start = $date->setTimeFromTimeString($scheduleForDay['start']);
        $end = $date->setTimeFromTimeString($scheduleForDay['end']);
        $durationInMinutes = $serviceDuration->getMinutes();

        $availableSlots = [];
        $currentSlot = $start;

        while ($currentSlot->addMinutes($durationInMinutes) <= $end) {
            $isBooked = false;
            foreach ($bookedSlots as $bookedSlot) {
                $bookedStart = CarbonImmutable::parse($bookedSlot['start']);
                $bookedEnd = CarbonImmutable::parse($bookedSlot['end']);
                if ($currentSlot < $bookedEnd && $currentSlot->addMinutes($durationInMinutes) > $bookedStart) {
                    $isBooked = true;
                    break;
                }
            }

            if (!$isBooked) {
                $availableSlots[] = $currentSlot->format('H:i');
            }

            $currentSlot = $currentSlot->addMinutes(15); // Assuming 15-minute intervals for slots
        }

        return $availableSlots;
    }
}
