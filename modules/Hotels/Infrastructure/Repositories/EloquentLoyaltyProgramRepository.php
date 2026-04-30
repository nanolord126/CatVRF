<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Repositories;

use Modules\Hotels\Domain\Entities\LoyaltyProgram;
use Modules\Hotels\Domain\Repositories\LoyaltyProgramRepositoryInterface;
use Modules\Hotels\Domain\ValueObjects\LoyaltyProgramId;
use Modules\Hotels\Domain\ValueObjects\VenueId;
use Modules\Hotels\Domain\ValueObjects\TenantId;
use Modules\Hotels\Domain\Enums\GuestLoyaltyLevel;
use Modules\Hotels\Infrastructure\Models\LoyaltyProgramModel;

final class EloquentLoyaltyProgramRepository implements LoyaltyProgramRepositoryInterface
{
    public function save(LoyaltyProgram $program): void
    {
        $model = $program->id === 0
            ? LoyaltyProgramModel::fromDomain($program)
            : LoyaltyProgramModel::findOrFail($program->id);

        $model->fill(LoyaltyProgramModel::fromDomain($program)->toArray());
        $model->save();

        if ($program->id === 0) {
            $model->id = $model->fresh()->id;
        }
    }

    public function findById(LoyaltyProgramId $id): ?LoyaltyProgram
    {
        $model = LoyaltyProgramModel::find($id->value);
        return $model?->toDomain();
    }

    public function findByVenue(VenueId $venueId): array
    {
        return LoyaltyProgramModel::where('venue_id', $venueId->value)
            ->orderBy('level')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findByTenant(TenantId $tenantId): array
    {
        return LoyaltyProgramModel::where('tenant_id', $tenantId->value)
            ->orderBy('level')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findByVenueAndLevel(VenueId $venueId, GuestLoyaltyLevel $level): ?LoyaltyProgram
    {
        $model = LoyaltyProgramModel::where('venue_id', $venueId->value)
            ->where('level', $level->value)
            ->where('is_active', true)
            ->first();

        return $model?->toDomain();
    }

    public function findActiveByVenue(VenueId $venueId): array
    {
        return LoyaltyProgramModel::where('venue_id', $venueId->value)
            ->where('is_active', true)
            ->orderBy('level')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function delete(LoyaltyProgramId $id): void
    {
        LoyaltyProgramModel::findOrFail($id->value)->delete();
    }
}
