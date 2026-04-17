<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Infrastructure\Services;

use App\Domains\RealEstate\Domain\Enums\PropertyStatusEnum;
use App\Domains\RealEstate\Domain\Entities\Property;
use App\Domains\RealEstate\Domain\Services\PropertySearchServiceInterface;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\Area;
use App\Domains\RealEstate\Domain\ValueObjects\Coordinate;
use App\Domains\RealEstate\Domain\ValueObjects\Price;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use App\Domains\RealEstate\Infrastructure\Eloquent\Models\PropertyModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Реализует поиск объектов на основе Eloquent + MySQL ST_Distance_Sphere.
 *
 * Таблица real_estate_properties должна иметь столбцы latitude/longitude
 * (DOUBLE) с индексом для поддержки геопоиска.
 */
final readonly class DatabasePropertySearchService implements PropertySearchServiceInterface
{
    public function search(
        ?string $query,
        ?\App\Domains\RealEstate\Domain\Enums\PropertyTypeEnum $type,
        ?int $minPriceKopecks,
        ?int $maxPriceKopecks,
        ?float $minAreaSqm,
        ?int $rooms,
        ?float $lat,
        ?float $lon,
        ?int $radiusMeters,
        int $tenantId,
        int $perPage,
        int $page,
    ): Collection {
        return $this->buildQuery(
            $query, $type, $minPriceKopecks, $maxPriceKopecks,
            $minAreaSqm, $rooms, $lat, $lon, $radiusMeters, $tenantId,
        )
            ->with(['photos'])
            ->orderByDesc('created_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn (PropertyModel $m) => $this->toDomain($m));
    }

    public function count(
        ?string $query,
        ?\App\Domains\RealEstate\Domain\Enums\PropertyTypeEnum $type,
        ?int $minPriceKopecks,
        ?int $maxPriceKopecks,
        ?float $minAreaSqm,
        ?int $rooms,
        ?float $lat,
        ?float $lon,
        ?int $radiusMeters,
        int $tenantId,
    ): int {
        return $this->buildQuery(
            $query, $type, $minPriceKopecks, $maxPriceKopecks,
            $minAreaSqm, $rooms, $lat, $lon, $radiusMeters, $tenantId,
        )->count();
    }

    /**
     * Найти ближайшие объекты по координатам.
     *
     * @return Collection<Property>
     */
    public function findNearby(
        float $lat,
        float $lon,
        int $radiusMeters,
        int $tenantId,
        int $limit = 10,
    ): Collection {
        return PropertyModel::withoutGlobalScope('tenant')
            ->with(['photos'])
            ->where('tenant_id', $tenantId)
            ->where('status', PropertyStatusEnum::Active->value)
            ->whereRaw(
                'ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?)) <= ?',
                [$lon, $lat, $radiusMeters]
            )
            ->selectRaw(
                '*, ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?)) AS distance_meters',
                [$lon, $lat]
            )
            ->orderBy('distance_meters')
            ->limit($limit)
            ->get()
            ->map(fn (PropertyModel $m) => $this->toDomain($m));
    }

    // -------------------------------------------------------------------------
    //  Helpers
    // -------------------------------------------------------------------------

    private function buildQuery(
        ?string $query,
        ?\App\Domains\RealEstate\Domain\Enums\PropertyTypeEnum $type,
        ?int $minPriceKopecks,
        ?int $maxPriceKopecks,
        ?float $minAreaSqm,
        ?int $rooms,
        ?float $lat,
        ?float $lon,
        ?int $radiusMeters,
        int $tenantId,
    ): Builder {
        $builder = PropertyModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', PropertyStatusEnum::Active->value);

        if ($query !== null && $query !== '') {
            $builder->where(static function (Builder $q) use ($query): void {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('address', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });
        }

        if ($type !== null) {
            $builder->where('type', $type->value);
        }

        if ($minPriceKopecks !== null) {
            $builder->where('price_kopecks', '>=', $minPriceKopecks);
        }

        if ($maxPriceKopecks !== null) {
            $builder->where('price_kopecks', '<=', $maxPriceKopecks);
        }

        if ($minAreaSqm !== null) {
            $builder->where('area_sqm', '>=', $minAreaSqm);
        }

        if ($rooms !== null) {
            $builder->where('rooms', $rooms);
        }

        if ($lat !== null && $lon !== null && $radiusMeters !== null) {
            $builder->whereRaw(
                'ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?)) <= ?',
                [$lon, $lat, $radiusMeters]
            );
        }

        return $builder;
    }

    private function toDomain(PropertyModel $model): Property
    {
        $photos = $model->relationLoaded('photos')
            ? $model->photos->map(fn ($p) => ['url' => $p->url, 'caption' => $p->caption])->all()
            : [];

        return new Property(
            id:            PropertyId::fromString($model->id),
            tenantId:      $model->tenant_id,
            agentId:       AgentId::fromString($model->agent_id),
            title:         $model->title,
            description:   $model->description,
            address:       $model->address,
            coordinates:   new Coordinate($model->latitude, $model->longitude),
            type:          \App\Domains\RealEstate\Domain\Enums\PropertyTypeEnum::from($model->type),
            price:         Price::fromKopecks($model->price_kopecks),
            area:          new Area($model->area_sqm),
            rooms:         $model->rooms,
            floor:         $model->floor,
            totalFloors:   $model->total_floors,
            status:        PropertyStatusEnum::from($model->status),
            photos:        $photos,
            documents:     [],
            correlationId: $model->correlation_id ?? '',
        );
    }
}
