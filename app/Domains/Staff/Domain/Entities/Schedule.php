<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use App\Domains\Staff\Domain\ValueObjects\ScheduleId;
use App\Domains\Staff\Domain\ValueObjects\StaffId;
use Carbon\Carbon;

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
 * @package App\Domains\Staff\Domain\Entities
 */
final class Schedule
{
    private ScheduleId $id;
    private StaffId $staffId;
    private Carbon $startTime;
    private Carbon $endTime;

    public function __construct(ScheduleId $id, StaffId $staffId, Carbon $startTime, Carbon $endTime)
    {
        if ($startTime->greaterThanOrEqualTo($endTime)) {
            throw new \InvalidArgumentException('Start time must be before end time.');
        }

        $this->id = $id;
        $this->staffId = $staffId;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    public function getId(): ScheduleId
    {
        return $this->id;
    }

    public function getStaffId(): StaffId
    {
        return $this->staffId;
    }

    public function getStartTime(): Carbon
    {
        return $this->startTime;
    }

    public function getEndTime(): Carbon
    {
        return $this->endTime;
    }
}
