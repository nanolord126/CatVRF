<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Repositories;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Entities\WorkoutSession;

interface WorkoutSessionRepositoryInterface
{
    public function findById(int $id): ?WorkoutSession;

    public function findByScheduleSlotId(int $scheduleSlotId): ?WorkoutSession;

    public function findByTrainerId(int $trainerId, CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    public function findByVenueId(int $venueId, CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    public function findByDateRange(int $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    public function save(WorkoutSession $session): WorkoutSession;

    public function delete(int $id): void;
}
