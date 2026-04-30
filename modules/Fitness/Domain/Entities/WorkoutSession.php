<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class WorkoutSession
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $scheduleSlotId,
        public int $trainerId,
        public int $venueId,
        public int $workoutTypeId,
        public CarbonImmutable $startTime,
        public CarbonImmutable $endTime,
        public int $actualParticipants,
        public ?string $trainerNotes,
        public ?array $exercisesPerformed,
        public ?float $averageRating,
        public int $totalRatings,
        public ?array $equipmentUsed,
        public ?string $musicPlaylist,
        public ?float $temperature,
        public ?float $humidity,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $scheduleSlotId,
        int $trainerId,
        int $venueId,
        int $workoutTypeId,
        CarbonImmutable $startTime,
        CarbonImmutable $endTime,
        int $actualParticipants = 0,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            scheduleSlotId: $scheduleSlotId,
            trainerId: $trainerId,
            venueId: $venueId,
            workoutTypeId: $workoutTypeId,
            startTime: $startTime,
            endTime: $endTime,
            actualParticipants: $actualParticipants,
            trainerNotes: null,
            exercisesPerformed: null,
            averageRating: null,
            totalRatings: 0,
            equipmentUsed: null,
            musicPlaylist: null,
            temperature: null,
            humidity: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function addParticipant(): self
    {
        return new self(
            ...get_object_vars($this),
            actualParticipants: $this->actualParticipants + 1,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function addTrainerNotes(string $notes): self
    {
        return new self(
            ...get_object_vars($this),
            trainerNotes: $notes,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function addExercises(array $exercises): self
    {
        return new self(
            ...get_object_vars($this),
            exercisesPerformed: $exercises,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function addRating(float $rating): self
    {
        $newTotal = $this->totalRatings + 1;
        $newAverage = $this->averageRating === null
            ? $rating
            : (($this->averageRating * $this->totalRatings) + $rating) / $newTotal;

        return new self(
            ...get_object_vars($this),
            averageRating: round($newAverage, 2),
            totalRatings: $newTotal,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function getDuration(): int
    {
        return $this->startTime->diffInMinutes($this->endTime);
    }

    public function getOccupancyRate(int $capacity): float
    {
        if ($capacity === 0) {
            return 0.0;
        }

        return ($this->actualParticipants / $capacity) * 100;
    }
}
