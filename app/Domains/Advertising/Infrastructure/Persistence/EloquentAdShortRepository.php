<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Persistence;

use App\Domains\Advertising\Domain\Entities\AdShort;
use App\Domains\Advertising\Domain\Interfaces\AdShortRepositoryInterface;
use App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models\EloquentAdShort;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Eloquent AdShort Repository
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class EloquentAdShortRepository implements AdShortRepositoryInterface
{
    public function save(AdShort $adShort): AdShort
    {
        $eloquent = $adShort->id === 0
            ? EloquentAdShort::fromDomain($adShort)
            : EloquentAdShort::findOrFail($adShort->id);

        $eloquent->fill(EloquentAdShort::fromDomain($adShort)->toArray());
        $eloquent->save();

        return $eloquent->toDomain();
    }

    public function findById(int $id): ?AdShort
    {
        $eloquent = EloquentAdShort::find($id);

        return $eloquent?->toDomain();
    }

    public function findByUuid(string $uuid): ?AdShort
    {
        $eloquent = EloquentAdShort::where('uuid', $uuid)->first();

        return $eloquent?->toDomain();
    }

    /** @return Collection<int, AdShort> */
    public function findByTenant(int $tenantId): Collection
    {
        return EloquentAdShort::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (EloquentAdShort $model) => $model->toDomain());
    }

    /** @return Collection<int, AdShort> */
    public function findActive(): Collection
    {
        return EloquentAdShort::where('status', 'active')
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->get()
            ->map(fn (EloquentAdShort $model) => $model->toDomain());
    }

    /** @return Collection<int, AdShort> */
    public function findActiveByTenant(int $tenantId): Collection
    {
        return EloquentAdShort::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->get()
            ->map(fn (EloquentAdShort $model) => $model->toDomain());
    }

    public function updateSpent(int $id, int $spent): bool
    {
        return EloquentAdShort::where('id', $id)->update(['spent' => $spent]) > 0;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return EloquentAdShort::where('id', $id)->update(['status' => $status]) > 0;
    }

    public function delete(int $id): bool
    {
        return EloquentAdShort::where('id', $id)->delete() > 0;
    }
}
