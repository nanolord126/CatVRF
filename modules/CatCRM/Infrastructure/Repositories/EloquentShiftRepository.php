<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Staff\Repositories\ShiftRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Shift;
use Modules\CatCRM\Domain\Staff\ValueObjects\ShiftId;
use Modules\CatCRM\Domain\Staff\ValueObjects\ShiftStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * EloquentShiftRepository — Layer 7: Repository Implementation
 */
final class EloquentShiftRepository implements ShiftRepositoryInterface
{
    public function findById(ShiftId $id): ?Shift
    {
        $record = DB::table('staff_shifts')->where('id', $id->value)->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findByEmployee(int $tenantId, int $employeeId): array
    {
        $records = DB::table('staff_shifts')
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findByDateRange(int $tenantId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $records = DB::table('staff_shifts')
            ->where('tenant_id', $tenantId)
            ->where('start_time', '>=', $start->toDateTimeString())
            ->where('end_time', '<=', $end->toDateTimeString())
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findActiveByEmployee(int $tenantId, int $employeeId): ?Shift
    {
        $record = DB::table('staff_shifts')
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('status', 'in_progress')
            ->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function save(Shift $shift): bool
    {
        $data = [
            'tenant_id' => $shift->tenantId,
            'employee_id' => $shift->employeeId,
            'start_time' => $shift->startTime->toDateTimeString(),
            'end_time' => $shift->endTime->toDateTimeString(),
            'location' => $shift->location,
            'status' => $shift->status->value,
            'latitude' => $shift->latitude,
            'longitude' => $shift->longitude,
            'metadata' => json_encode($shift->metadata),
            'updated_at' => $shift->updatedAt->toDateTimeString(),
        ];

        if ($shift->id->value === 0) {
            $data['created_at'] = $shift->createdAt->toDateTimeString();
            $id = DB::table('staff_shifts')->insertGetId($data);
            return $id > 0;
        } else {
            return DB::table('staff_shifts')
                ->where('id', $shift->id->value)
                ->update($data) > 0;
        }
    }

    public function delete(ShiftId $id): bool
    {
        return DB::table('staff_shifts')
            ->where('id', $id->value)
            ->delete() > 0;
    }

    private function mapToEntity(array $record): Shift
    {
        return new Shift(
            id: ShiftId::fromInt((int) $record['id']),
            tenantId: (int) $record['tenant_id'],
            employeeId: (int) $record['employee_id'],
            startTime: CarbonImmutable::parse($record['start_time']),
            endTime: CarbonImmutable::parse($record['end_time']),
            location: $record['location'] ?? null,
            status: ShiftStatus::from($record['status']),
            latitude: $record['latitude'] ? (float) $record['latitude'] : null,
            longitude: $record['longitude'] ? (float) $record['longitude'] : null,
            metadata: json_decode($record['metadata'] ?? '{}', true),
            createdAt: CarbonImmutable::parse($record['created_at']),
            updatedAt: CarbonImmutable::parse($record['updated_at']),
        );
    }
}
