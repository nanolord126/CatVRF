<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Staff\Repositories\SkillRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Skill;
use Modules\CatCRM\Domain\Staff\ValueObjects\SkillId;
use Modules\CatCRM\Domain\Staff\ValueObjects\SkillLevel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * EloquentSkillRepository — Layer 7: Repository Implementation
 */
final class EloquentSkillRepository implements SkillRepositoryInterface
{
    public function findById(SkillId $id): ?Skill
    {
        $record = DB::table('staff_skills')->where('id', $id->value)->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findByTenant(int $tenantId): array
    {
        $records = DB::table('staff_skills')
            ->where('tenant_id', $tenantId)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findByCategory(int $tenantId, string $category): array
    {
        $records = DB::table('staff_skills')
            ->where('tenant_id', $tenantId)
            ->where('category', $category)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findByEmployee(int $tenantId, int $employeeId): array
    {
        // Get employee skills from employees table
        $employee = DB::table('staff_employees')
            ->where('tenant_id', $tenantId)
            ->where('id', $employeeId)
            ->first();

        if (!$employee) {
            return [];
        }

        $skillIds = json_decode($employee['skills'] ?? '[]', true);
        
        if (empty($skillIds)) {
            return [];
        }

        $records = DB::table('staff_skills')
            ->whereIn('id', $skillIds)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function save(Skill $skill): bool
    {
        $data = [
            'tenant_id' => $skill->tenantId,
            'name' => $skill->name,
            'category' => $skill->category,
            'description' => $skill->description,
            'level' => $skill->level->value,
            'proficiency' => $skill->proficiency,
            'last_assessed' => $skill->lastAssessed?->toDateTimeString(),
            'metadata' => json_encode($skill->metadata),
            'updated_at' => $skill->updatedAt->toDateTimeString(),
        ];

        if ($skill->id->value === 0) {
            $data['created_at'] = $skill->createdAt->toDateTimeString();
            $id = DB::table('staff_skills')->insertGetId($data);
            return $id > 0;
        } else {
            return DB::table('staff_skills')
                ->where('id', $skill->id->value)
                ->update($data) > 0;
        }
    }

    public function delete(SkillId $id): bool
    {
        return DB::table('staff_skills')
            ->where('id', $id->value)
            ->delete() > 0;
    }

    private function mapToEntity(array $record): Skill
    {
        return new Skill(
            id: SkillId::fromInt((int) $record['id']),
            tenantId: (int) $record['tenant_id'],
            name: $record['name'],
            category: $record['category'],
            description: $record['description'],
            level: SkillLevel::from($record['level']),
            proficiency: (int) $record['proficiency'],
            lastAssessed: $record['last_assessed'] ? CarbonImmutable::parse($record['last_assessed']) : null,
            metadata: json_decode($record['metadata'] ?? '{}', true),
            createdAt: CarbonImmutable::parse($record['created_at']),
            updatedAt: CarbonImmutable::parse($record['updated_at']),
        );
    }
}
