<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Persistence;

use App\Domains\Advertising\Domain\Entities\Publisher;
use App\Domains\Advertising\Domain\Interfaces\PublisherRepositoryInterface;
use App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models\EloquentPublisher;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent Publisher Repository
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class EloquentPublisherRepository implements PublisherRepositoryInterface
{
    public function save(Publisher $publisher): Publisher
    {
        $eloquent = $publisher->id === 0
            ? EloquentPublisher::fromDomain($publisher)
            : EloquentPublisher::findOrFail($publisher->id);

        $eloquent->fill(EloquentPublisher::fromDomain($publisher)->toArray());
        $eloquent->save();

        return $eloquent->toDomain();
    }

    public function findById(int $id): ?Publisher
    {
        $eloquent = EloquentPublisher::find($id);

        return $eloquent?->toDomain();
    }

    public function findByUuid(string $uuid): ?Publisher
    {
        $eloquent = EloquentPublisher::where('uuid', $uuid)->first();

        return $eloquent?->toDomain();
    }

    /** @return Collection<int, Publisher> */
    public function findByTenant(int $tenantId): Collection
    {
        return EloquentPublisher::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (EloquentPublisher $model) => $model->toDomain());
    }

    /** @return Collection<int, Publisher> */
    public function findActive(): Collection
    {
        return EloquentPublisher::where('status', 'active')
            ->get()
            ->map(fn (EloquentPublisher $model) => $model->toDomain());
    }

    public function findByApiKey(string $apiKey): ?Publisher
    {
        $eloquent = EloquentPublisher::where('api_key', $apiKey)->first();

        return $eloquent?->toDomain();
    }

    public function updateStatus(int $id, string $status): bool
    {
        return EloquentPublisher::where('id', $id)->update(['status' => $status]) > 0;
    }

    public function updateApiKey(int $id, string $apiKey): bool
    {
        return EloquentPublisher::where('id', $id)->update(['api_key' => $apiKey]) > 0;
    }

    public function delete(int $id): bool
    {
        return EloquentPublisher::where('id', $id)->delete() > 0;
    }
}
