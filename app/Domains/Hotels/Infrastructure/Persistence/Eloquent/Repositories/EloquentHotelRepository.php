<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domains\Hotels\Domain\Entities\Hotel;
use App\Domains\Hotels\Domain\Repositories\HotelRepositoryInterface;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Models\HotelModel;
use Illuminate\Support\Collection;
use App\Application\Exceptions\NotFoundException;

final class EloquentHotelRepository implements HotelRepositoryInterface
{
    public function find(HotelId $id): ?Hotel
    {
        $hotelModel = HotelModel::find($id->toString());

        if (!$hotelModel) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        return $hotelModel->toDomainEntity();
    }

    public function findByTenant(int $tenantId): Collection
    {
        return HotelModel::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (HotelModel $model) => $model->toDomainEntity());
    }

    public function save(Hotel $hotel): void
    {
        $hotelModel = HotelModel::find($hotel->getId()->toString());

        if (!$hotelModel) {
            $hotelModel = new HotelModel();
            $hotelModel->id = $hotel->getId()->toString();
        }

        $hotelModel->fill($hotel->toArray());
        $hotelModel->save();
    }

    public function delete(HotelId $id): bool
    {
        $hotelModel = HotelModel::find($id->toString());

        if (!$hotelModel) {
            throw new NotFoundException('Hotel not found.');
        }

        return $hotelModel->delete();
    }

    public function search(array $criteria): Collection
    {
        $query = HotelModel::query();

        if (isset($criteria['city'])) {
            $query->where('address->city', $criteria['city']);
        }

        if (isset($criteria['name'])) {
            $query->where('name', 'like', '%' . $criteria['name'] . '%');
        }
        
        if (isset($criteria['rating'])) {
            $query->where('rating', '>=', $criteria['rating']);
        }

        return $query->get()->map(fn (HotelModel $model) => $model->toDomainEntity());
    }
}
