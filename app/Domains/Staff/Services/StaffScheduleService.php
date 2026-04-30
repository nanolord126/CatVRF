<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Entities\StaffShift;
use App\Domains\Staff\Domain\Entities\StaffTimeoff;
use App\Domains\Staff\Domain\Entities\StaffShiftSwap;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * StaffScheduleService — сервис управления графиком сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Управляет сменами, подменами, отпусками, чек-ин/чек-аут с геолокацией.
 */
final class StaffScheduleService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Создаёт смену.
     */
    public function createShift(array $data): StaffShift
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_shift_create',
            'staff_id' => $data['staff_id'],
            'tenant_id' => $data['tenant_id'],
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $shift = StaffShift::create([
            'tenant_id' => $data['tenant_id'],
            'staff_id' => $data['staff_id'],
            'shift_date' => $data['shift_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_minutes' => $data['duration_minutes'],
            'shift_type' => $data['shift_type'] ?? 'regular',
            'location' => $data['location'] ?? null,
            'status' => 'scheduled',
            'checked_in_at' => null,
            'checked_out_at' => null,
            'check_in_method' => null,
            'check_in_lat' => null,
            'check_in_lng' => null,
            'check_out_lat' => null,
            'check_out_lng' => null,
            'notes' => $data['notes'] ?? null,
        ]);

        Cache::tags(['staff_schedule', 'staff:' . $data['staff_id']])->flush();

        $this->logCreated('staff_shift', $shift->id, [
            'staff_id' => $data['staff_id'],
            'shift_date' => $data['shift_date'],
        ]);

        return $shift;
    }

    /**
     * Чек-ин с геолокацией.
     */
    public function checkIn(int $shiftId, string $method, ?float $lat = null, ?float $lng = null): StaffShift
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_shift_checkin',
            'shift_id' => $shiftId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $shift = StaffShift::findOrFail($shiftId);

        if ($shift->status !== 'scheduled') {
            throw new \RuntimeException('Shift is not in scheduled status');
        }

        $shift->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
            'check_in_method' => $method,
            'check_in_lat' => $lat,
            'check_in_lng' => $lng,
        ]);

        Cache::tags(['staff_schedule', 'staff:' . $shift->staff_id])->flush();

        $this->logAction('shift_checked_in', [
            'entity_type' => 'staff_shift',
            'entity_id' => $shiftId,
            'staff_id' => $shift->staff_id,
            'method' => $method,
        ]);

        return $shift->fresh();
    }

    /**
     * Чек-аут с геолокацией.
     */
    public function checkOut(int $shiftId, ?float $lat = null, ?float $lng = null): StaffShift
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_shift_checkout',
            'shift_id' => $shiftId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $shift = StaffShift::findOrFail($shiftId);

        if ($shift->status !== 'checked_in') {
            throw new \RuntimeException('Shift is not checked in');
        }

        $shift->update([
            'status' => 'checked_out',
            'checked_out_at' => now(),
            'check_out_lat' => $lat,
            'check_out_lng' => $lng,
        ]);

        Cache::tags(['staff_schedule', 'staff:' . $shift->staff_id])->flush();

        $this->logAction('shift_checked_out', [
            'entity_type' => 'staff_shift',
            'entity_id' => $shiftId,
            'staff_id' => $shift->staff_id,
        ]);

        return $shift->fresh();
    }

    /**
     * Создаёт запрос на отпуск.
     */
    public function createTimeOff(array $data): StaffTimeoff
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_timeoff_create',
            'staff_id' => $data['staff_id'],
            'tenant_id' => $data['tenant_id'],
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $durationDays = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;

        $timeoff = StaffTimeoff::create([
            'tenant_id' => $data['tenant_id'],
            'staff_id' => $data['staff_id'],
            'approved_by' => null,
            'type' => $data['type'] ?? 'vacation',
            'status' => 'pending',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'duration_days' => $durationDays,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'approved_at' => null,
            'rejection_reason' => null,
            'attachment_url' => $data['attachment_url'] ?? null,
        ]);

        Cache::tags(['staff_schedule', 'staff:' . $data['staff_id']])->flush();

        $this->logCreated('staff_timeoff', $timeoff->id, [
            'staff_id' => $data['staff_id'],
            'type' => $data['type'],
            'duration_days' => $durationDays,
        ]);

        return $timeoff;
    }

    /**
     * Одобряет отпуск.
     */
    public function approveTimeOff(int $timeoffId, int $approvedBy): StaffTimeoff
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_timeoff_approve',
            'approved_by' => $approvedBy,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $timeoff = StaffTimeoff::findOrFail($timeoffId);

        $timeoff->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        Cache::tags(['staff_schedule', 'staff:' . $timeoff->staff_id])->flush();

        $this->logAction('timeoff_approved', [
            'entity_type' => 'staff_timeoff',
            'entity_id' => $timeoffId,
            'approved_by' => $approvedBy,
        ]);

        return $timeoff->fresh();
    }

    /**
     * Создаёт запрос на подмену смены.
     */
    public function createShiftSwap(array $data): StaffShiftSwap
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_shift_swap_create',
            'original_staff_id' => $data['original_staff_id'],
            'tenant_id' => $data['tenant_id'],
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $swap = StaffShiftSwap::create([
            'tenant_id' => $data['tenant_id'],
            'original_staff_id' => $data['original_staff_id'],
            'replacement_staff_id' => $data['replacement_staff_id'],
            'shift_id' => $data['shift_id'],
            'status' => 'pending',
            'reason' => $data['reason'] ?? null,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        Cache::tags(['staff_schedule'])->flush();

        $this->logCreated('staff_shift_swap', $swap->id, [
            'original_staff_id' => $data['original_staff_id'],
            'replacement_staff_id' => $data['replacement_staff_id'],
            'shift_id' => $data['shift_id'],
        ]);

        return $swap;
    }

    /**
     * Одобряет подмену смены.
     */
    public function approveShiftSwap(int $swapId, int $approvedBy): StaffShiftSwap
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_shift_swap_approve',
            'approved_by' => $approvedBy,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $swap = StaffShiftSwap::findOrFail($swapId);

        DB::transaction(function () use ($swap, $approvedBy) {
            $swap->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            // Обновляем смену на нового сотрудника
            $shift = $swap->shift;
            $shift->update(['staff_id' => $swap->replacement_staff_id]);

            Cache::tags(['staff_schedule'])->flush();

            $this->logAction('shift_swap_approved', [
                'entity_type' => 'staff_shift_swap',
                'entity_id' => $swapId,
                'approved_by' => $approvedBy,
            ]);
        });

        return $swap->fresh();
    }

    /**
     * Получает смены сотрудника на период.
     */
    public function getShiftsForPeriod(int $staffId, Carbon $start, Carbon $end): array
    {
        $cacheKey = "staff_shifts:{$staffId}:{$start->toDateString()}:{$end->toDateString()}";

        return Cache::tags(['staff_schedule', 'staff:' . $staffId])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($staffId, $start, $end) {
                return StaffShift::where('staff_id', $staffId)
                    ->whereBetween('shift_date', [$start, $end])
                    ->orderBy('shift_date')
                    ->get()
                    ->map(fn ($s) => [
                        'id' => $s->id,
                        'shift_date' => $s->shift_date->toDateString(),
                        'start_time' => $s->start_time->toTimeString(),
                        'end_time' => $s->end_time->toTimeString(),
                        'shift_type' => $s->shift_type,
                        'location' => $s->location,
                        'status' => $s->status,
                        'duration_hours' => $s->duration_hours,
                    ])
                    ->toArray();
            },
        );
    }

    /**
     * Получает предстоящие смены (сегодня + 7 дней).
     */
    public function getUpcomingShifts(int $staffId, int $days = 7): array
    {
        return $this->getShiftsForPeriod($staffId, now(), now()->addDays($days));
    }
}
