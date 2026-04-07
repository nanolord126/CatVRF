<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\Staff\Domain\Repositories;

use App\Domains\Staff\Domain\Entities\Schedule;
use App\Domains\Staff\Domain\ValueObjects\ScheduleId;
use App\Domains\Staff\Domain\ValueObjects\StaffId;
use Illuminate\Support\Collection;

interface ScheduleRepositoryInterface
{
    public function find(ScheduleId $id): ?Schedule;

    public function findByStaff(StaffId $staffId): Collection;

    public function save(Schedule $schedule): void;

    public function delete(ScheduleId $id): void;
}
