<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Repositories;

use Modules\Hotels\Domain\Entities\Shift;
use Modules\Hotels\Domain\Repositories\ShiftRepositoryInterface;
use Modules\Hotels\Domain\ValueObjects\ShiftId;
use Modules\Hotels\Domain\ValueObjects\VenueId;
use Modules\Hotels\Domain\ValueObjects\TenantId;
use Modules\Hotels\Domain\ValueObjects\UserId;
use Carbon\CarbonImmutable;
use Modules\Hotels\Infrastructure\Models\ShiftModel;

final class EloquentShiftRepository implements ShiftRepositoryInterface
{
    public function save(Shift $shift): void
    {
        $model = $shift->id === 0
            ? ShiftModel::fromDomain($shift)
            : ShiftModel::findOrFail($shift->id);

        $model->fill(ShiftModel::fromDomain($shift)->toArray());
        $model->save();

        if ($shift->id === 0) {
            $model->id = $model->fresh()->id;
        }
    }

    public function findById(ShiftId $id): ?Shift
    {
        $model = ShiftModel::find($id->value);
        return $model?->toDomain();
    }

    public function findByVenue(VenueId $venueId): array
    {
        return ShiftModel::where('venue_id', $venueId->value)
            ->orderBy('start_time')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findByUser(UserId $userId): array
    {
        return ShiftModel::where('user_id', $userId->value)
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findByVenueAndDateRange(VenueId $venueId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return ShiftModel::where('venue_id', $venueId->value)
            ->whereBetween('start_time', [$start, $end])
            ->orderBy('start_time')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findActiveShifts(VenueId $venueId): array
    {
        return ShiftModel::where('venue_id', $venueId->value)
            ->where('status', 'active')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function delete(ShiftId $id): void
    {
        ShiftModel::findOrFail($id->value)->delete();
    }
}
