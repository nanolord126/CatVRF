<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Staff\Repositories\BadgeRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Badge;
use Modules\CatCRM\Domain\Staff\ValueObjects\BadgeId;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * EloquentBadgeRepository — Layer 7: Repository Implementation
 */
final class EloquentBadgeRepository implements BadgeRepositoryInterface
{
    public function findById(BadgeId $id): ?Badge
    {
        $record = DB::table('staff_badges')->where('id', $id->value)->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findByTenant(int $tenantId): array
    {
        $records = DB::table('staff_badges')
            ->where('tenant_id', $tenantId)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findByCategory(int $tenantId, string $category): array
    {
        $records = DB::table('staff_badges')
            ->where('tenant_id', $tenantId)
            ->where('category', $category)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function save(Badge $badge): bool
    {
        $data = [
            'tenant_id' => $badge->tenantId,
            'name' => $badge->name,
            'description' => $badge->description,
            'icon' => $badge->icon,
            'category' => $badge->category,
            'points_required' => $badge->pointsRequired,
            'condition' => $badge->condition,
            'is_rare' => $badge->isRare,
            'is_legendary' => $badge->isLegendary,
            'metadata' => json_encode($badge->metadata),
            'updated_at' => $badge->updatedAt->toDateTimeString(),
        ];

        if ($badge->id->value === 0) {
            $data['created_at'] = $badge->createdAt->toDateTimeString();
            $id = DB::table('staff_badges')->insertGetId($data);
            return $id > 0;
        } else {
            return DB::table('staff_badges')
                ->where('id', $badge->id->value)
                ->update($data) > 0;
        }
    }

    public function delete(BadgeId $id): bool
    {
        return DB::table('staff_badges')
            ->where('id', $id->value)
            ->delete() > 0;
    }

    private function mapToEntity(array $record): Badge
    {
        return new Badge(
            id: BadgeId::fromInt((int) $record['id']),
            tenantId: (int) $record['tenant_id'],
            name: $record['name'],
            description: $record['description'],
            icon: $record['icon'],
            category: $record['category'],
            pointsRequired: (int) $record['points_required'],
            condition: $record['condition'],
            isRare: (bool) $record['is_rare'],
            isLegendary: (bool) $record['is_legendary'],
            metadata: json_decode($record['metadata'] ?? '{}', true),
            createdAt: CarbonImmutable::parse($record['created_at']),
            updatedAt: CarbonImmutable::parse($record['updated_at']),
        );
    }
}
