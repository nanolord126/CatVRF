<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\BeautyMasters\Domain\Repositories\MasterScheduleRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\BlockedSlotRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\AppointmentRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class MasterScheduleService
{
    use WithAuditLogging;

    public function __construct(
        private MasterScheduleRepositoryInterface $scheduleRepository,
        private BlockedSlotRepositoryInterface $blockedSlotRepository,
        private AppointmentRepositoryInterface $appointmentRepository,
        private readonly AuditService $auditService,
    ) {
    }

    public function setMasterSchedule(
        int $masterId,
        int $venueId,
        string $dayOfWeek,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        ?\DateTimeImmutable $breakStart = null,
        ?\DateTimeImmutable $breakEnd = null,
        bool $isWorkingDay = true,
        ?\DateTimeImmutable $effectiveFrom = null,
        ?\DateTimeImmutable $effectiveUntil = null
    ): array {
        return DB::transaction(function () use (
            $masterId,
            $venueId,
            $dayOfWeek,
            $startTime,
            $endTime,
            $breakStart,
            $breakEnd,
            $isWorkingDay,
            $effectiveFrom,
            $effectiveUntil
        ) {
            if ($endTime <= $startTime) {
                throw new \InvalidArgumentException('End time must be after start time');
            }

            if ($breakStart && $breakEnd && $breakEnd <= $breakStart) {
                throw new \InvalidArgumentException('Break end must be after break start');
            }

            $schedule = [
                'master_id' => $masterId,
                'venue_id' => $venueId,
                'day_of_week' => strtolower($dayOfWeek),
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'break_start' => $breakStart?->format('H:i:s'),
                'break_end' => $breakEnd?->format('H:i:s'),
                'is_working_day' => $isWorkingDay,
                'effective_from' => $effectiveFrom?->format('Y-m-d'),
                'effective_until' => $effectiveUntil?->format('Y-m-d'),
            ];

            $savedSchedule = $this->scheduleRepository->save($schedule);

            $this->invalidateScheduleCache($masterId, $dayOfWeek);

            $this->logAction('master_schedule_updated', 'MasterSchedule', null, [
                'master_id' => $masterId,
                'day_of_week' => $dayOfWeek,
                'is_working_day' => $isWorkingDay,
            ], null, null);

            return $savedSchedule;
        });
    }

    public function blockTimeSlot(
        int $venueId,
        ?int $masterId,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        string $reason,
        ?string $description = null,
        ?int $createdBy = null
    ): array {
        return DB::transaction(function () use (
            $venueId,
            $masterId,
            $startTime,
            $endTime,
            $reason,
            $description,
            $createdBy
        ) {
            if ($endTime <= $startTime) {
                throw new \InvalidArgumentException('End time must be after start time');
            }

            $overlapping = $this->blockedSlotRepository->findOverlapping(
                $masterId,
                $startTime,
                $endTime
            );

            if (!$overlapping->isEmpty()) {
                throw new \RuntimeException('Time slot is already blocked');
            }

            if ($masterId) {
                $existingAppointments = $this->appointmentRepository->findOverlapping(
                    $masterId,
                    $startTime,
                    $endTime
                );

                if (!$existingAppointments->isEmpty()) {
                    throw new \RuntimeException('Cannot block slot with existing appointments');
                }
            }

            $slot = [
                'venue_id' => $venueId,
                'master_id' => $masterId,
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
                'reason' => $reason,
                'description' => $description,
                'created_by' => $createdBy,
            ];

            $savedSlot = $this->blockedSlotRepository->save($slot);

            if ($masterId) {
                $this->invalidateScheduleCache($masterId, strtolower($startTime->format('l')));
            }

            $this->logAction('time_slot_blocked', 'BlockedSlot', null, [
                'venue_id' => $venueId,
                'master_id' => $masterId,
                'reason' => $reason,
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
            ], $createdBy, null);

            return $savedSlot;
        });
    }

    public function unblockTimeSlot(int $slotId): bool
    {
        $result = $this->blockedSlotRepository->delete($slotId);

        if ($result) {
            $this->logAction('time_slot_unblocked', 'BlockedSlot', $slotId, []);
        }

        return $result;
    }

    public function getMasterSchedule(int $masterId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $cacheKey = "beauty:master_schedule:{$masterId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($masterId, $startDate, $endDate) {
            $schedules = $this->scheduleRepository->findByMasterId($masterId);
            $blockedSlots = $this->blockedSlotRepository->findByMasterId($masterId, $startDate, $endDate);
            $appointments = $this->appointmentRepository->findByMasterId($masterId, $startDate, $endDate);

            $result = [];
            $currentDate = $startDate;

            while ($currentDate <= $endDate) {
                $dayOfWeek = strtolower($currentDate->format('l'));
                $schedule = $schedules->first(fn($s) => $s['day_of_week'] === $dayOfWeek);

                $dayBlockedSlots = $blockedSlots->filter(function ($slot) use ($currentDate) {
                    $slotDate = new \DateTimeImmutable($slot['start_time']);
                    return $slotDate->format('Y-m-d') === $currentDate->format('Y-m-d');
                });

                $dayAppointments = $appointments->filter(function ($apt) use ($currentDate) {
                    $aptDate = $apt->startTime;
                    return $aptDate->format('Y-m-d') === $currentDate->format('Y-m-d');
                });

                $result[$currentDate->format('Y-m-d')] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day_of_week' => $dayOfWeek,
                    'is_working_day' => $schedule ? (bool) $schedule['is_working_day'] : false,
                    'working_hours' => $schedule ? [
                        'start' => $schedule['start_time'],
                        'end' => $schedule['end_time'],
                        'break_start' => $schedule['break_start'],
                        'break_end' => $schedule['break_end'],
                    ] : null,
                    'blocked_slots' => $dayBlockedSlots->toArray(),
                    'appointments' => $dayAppointments->map(fn($apt) => $apt->toArray())->toArray(),
                ];

                $currentDate = $currentDate->modify('+1 day');
            }

            return $result;
        });
    }

    public function isMasterAvailable(int $masterId, \DateTimeImmutable $dateTime): bool
    {
        $dayOfWeek = strtolower($dateTime->format('l'));
        $schedule = $this->scheduleRepository->findByMasterIdAndDay($masterId, $dayOfWeek);

        if (!$schedule || !$schedule['is_working_day']) {
            return false;
        }

        $workingStart = \DateTimeImmutable::createFromFormat(
            'H:i:s',
            $schedule['start_time']
        )->setDate((int) $dateTime->format('Y'), (int) $dateTime->format('m'), (int) $dateTime->format('d'));

        $workingEnd = \DateTimeImmutable::createFromFormat(
            'H:i:s',
            $schedule['end_time']
        )->setDate((int) $dateTime->format('Y'), (int) $dateTime->format('m'), (int) $dateTime->format('d'));

        if ($dateTime < $workingStart || $dateTime >= $workingEnd) {
            return false;
        }

        if ($schedule['break_start'] && $schedule['break_end']) {
            $breakStart = \DateTimeImmutable::createFromFormat(
                'H:i:s',
                $schedule['break_start']
            )->setDate((int) $dateTime->format('Y'), (int) $dateTime->format('m'), (int) $dateTime->format('d'));

            $breakEnd = \DateTimeImmutable::createFromFormat(
                'H:i:s',
                $schedule['break_end']
            )->setDate((int) $dateTime->format('Y'), (int) $dateTime->format('m'), (int) $dateTime->format('d'));

            if ($dateTime >= $breakStart && $dateTime < $breakEnd) {
                return false;
            }
        }

        $blockedSlots = $this->blockedSlotRepository->findByMasterId(
            $masterId,
            $dateTime->modify('-1 day'),
            $dateTime->modify('+1 day')
        );

        foreach ($blockedSlots as $slot) {
            $slotStart = new \DateTimeImmutable($slot['start_time']);
            $slotEnd = new \DateTimeImmutable($slot['end_time']);

            if ($dateTime >= $slotStart && $dateTime < $slotEnd) {
                return false;
            }
        }

        return true;
    }

    public function getVenueSchedule(int $venueId, \DateTimeImmutable $date): array
    {
        $schedules = $this->scheduleRepository->findByVenueId($venueId);
        $dayOfWeek = strtolower($date->format('l'));

        return $schedules
            ->filter(fn($s) => $s['day_of_week'] === $dayOfWeek && $s['is_working_day'])
            ->groupBy('master_id')
            ->map(function ($masterSchedules) {
                return $masterSchedules->first();
            })
            ->toArray();
    }

    private function invalidateScheduleCache(int $masterId, string $dayOfWeek): void
    {
        $pattern = "beauty:master_schedule:{$masterId}:*";
        $pattern2 = "beauty:available_slots:{$masterId}:*";

        Cache::forget($pattern);
        Cache::forget($pattern2);
    }
}
