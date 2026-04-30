<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Staff\Repositories\AchievementRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Achievement;
use Modules\CatCRM\Domain\Staff\ValueObjects\AchievementId;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * EloquentAchievementRepository — Layer 7: Repository Implementation
 */
final class EloquentAchievementRepository implements AchievementRepositoryInterface
{
    public function findById(AchievementId $id): ?Achievement
    {
        $record = DB::table('staff_achievements')->where('id', $id->value)->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findByEmployee(int $tenantId, int $employeeId): array
    {
        $records = DB::table('staff_achievements')
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findByBadge(int $tenantId, int $badgeId): array
    {
        $records = DB::table('staff_achievements')
            ->where('tenant_id', $tenantId)
            ->where('badge_id', $badgeId)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function save(Achievement $achievement): bool
    {
        $data = [
            'tenant_id' => $achievement->tenantId,
            'employee_id' => $achievement->employeeId,
            'badge_id' => $achievement->badgeId,
            'badge_name' => $achievement->badgeName,
            'badge_icon' => $achievement->badgeIcon,
            'points_awarded' => $achievement->pointsAwarded,
            'achieved_at' => $achievement->achievedAt->toDateTimeString(),
            'metadata' => json_encode($achievement->metadata),
            'updated_at' => $achievement->updatedAt->toDateTimeString(),
        ];

        if ($achievement->id->value === 0) {
            $data['created_at'] = $achievement->createdAt->toDateTimeString();
            $id = DB::table('staff_achievements')->insertGetId($data);
            return $id > 0;
        } else {
            return DB::table('staff_achievements')
                ->where('id', $achievement->id->value)
                ->update($data) > 0;
        }
    }

    public function delete(AchievementId $id): bool
    {
        return DB::table('staff_achievements')
            ->where('id', $id->value)
            ->delete() > 0;
    }

    private function mapToEntity(array $record): Achievement
    {
        return new Achievement(
            id: AchievementId::fromInt((int) $record['id']),
            tenantId: (int) $record['tenant_id'],
            employeeId: (int) $record['employee_id'],
            badgeId: (int) $record['badge_id'],
            badgeName: $record['badge_name'],
            badgeIcon: $record['badge_icon'],
            pointsAwarded: (int) $record['points_awarded'],
            achievedAt: CarbonImmutable::parse($record['achieved_at']),
            metadata: json_decode($record['metadata'] ?? '{}', true),
            createdAt: CarbonImmutable::parse($record['created_at']),
            updatedAt: CarbonImmutable::parse($record['updated_at']),
        );
    }
}
