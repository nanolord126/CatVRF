<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Staff\Repositories\MentorshipRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Mentorship;
use Modules\CatCRM\Domain\Staff\ValueObjects\MentorshipId;
use Modules\CatCRM\Domain\Staff\ValueObjects\MentorshipStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * EloquentMentorshipRepository — Layer 7: Repository Implementation
 */
final class EloquentMentorshipRepository implements MentorshipRepositoryInterface
{
    public function findById(MentorshipId $id): ?Mentorship
    {
        $record = DB::table('staff_mentorships')->where('id', $id->value)->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findByMentor(int $tenantId, int $mentorId): array
    {
        $records = DB::table('staff_mentorships')
            ->where('tenant_id', $tenantId)
            ->where('mentor_id', $mentorId)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findByMentee(int $tenantId, int $menteeId): array
    {
        $records = DB::table('staff_mentorships')
            ->where('tenant_id', $tenantId)
            ->where('mentee_id', $menteeId)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findActiveByMentor(int $tenantId, int $mentorId): array
    {
        $records = DB::table('staff_mentorships')
            ->where('tenant_id', $tenantId)
            ->where('mentor_id', $mentorId)
            ->where('status', 'active')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findActiveByMentee(int $tenantId, int $menteeId): ?Mentorship
    {
        $record = DB::table('staff_mentorships')
            ->where('tenant_id', $tenantId)
            ->where('mentee_id', $menteeId)
            ->where('status', 'active')
            ->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function save(Mentorship $mentorship): bool
    {
        $data = [
            'tenant_id' => $mentorship->tenantId,
            'mentor_id' => $mentorship->mentorId,
            'mentee_id' => $mentorship->menteeId,
            'goals' => $mentorship->goals,
            'feedback' => $mentorship->feedback,
            'status' => $mentorship->status->value,
            'start_date' => $mentorship->startDate->toDateTimeString(),
            'end_date' => $mentorship->endDate?->toDateTimeString(),
            'metadata' => json_encode($mentorship->metadata),
            'updated_at' => $mentorship->updatedAt->toDateTimeString(),
        ];

        if ($mentorship->id->value === 0) {
            $data['created_at'] = $mentorship->createdAt->toDateTimeString();
            $id = DB::table('staff_mentorships')->insertGetId($data);
            return $id > 0;
        } else {
            return DB::table('staff_mentorships')
                ->where('id', $mentorship->id->value)
                ->update($data) > 0;
        }
    }

    public function delete(MentorshipId $id): bool
    {
        return DB::table('staff_mentorships')
            ->where('id', $id->value)
            ->delete() > 0;
    }

    private function mapToEntity(array $record): Mentorship
    {
        return new Mentorship(
            id: MentorshipId::fromInt((int) $record['id']),
            tenantId: (int) $record['tenant_id'],
            mentorId: (int) $record['mentor_id'],
            menteeId: (int) $record['mentee_id'],
            goals: $record['goals'],
            feedback: $record['feedback'] ?? null,
            status: MentorshipStatus::from($record['status']),
            startDate: CarbonImmutable::parse($record['start_date']),
            endDate: $record['end_date'] ? CarbonImmutable::parse($record['end_date']) : null,
            metadata: json_decode($record['metadata'] ?? '{}', true),
            createdAt: CarbonImmutable::parse($record['created_at']),
            updatedAt: CarbonImmutable::parse($record['updated_at']),
        );
    }
}
