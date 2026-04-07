<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Infrastructure\Eloquent\Repositories;

use App\Domains\RealEstate\Domain\Entities\Property;
use App\Domains\RealEstate\Domain\Enums\PropertyStatusEnum;
use App\Domains\RealEstate\Domain\Repository\PropertyRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\Area;
use App\Domains\RealEstate\Domain\ValueObjects\Coordinate;
use App\Domains\RealEstate\Domain\ValueObjects\Price;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use App\Domains\RealEstate\Infrastructure\Eloquent\Models\PropertyModel;
use App\Domains\RealEstate\Infrastructure\Eloquent\Models\PropertyPhotoModel;
use App\Domains\RealEstate\Infrastructure\Eloquent\Models\PropertyDocumentModel;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

final class EloquentPropertyRepository implements PropertyRepositoryInterface
{
    public function __construct(
        private readonly LoggerInterface $logger) {}
    public function findById(PropertyId $id): ?Property
    {
        $model = PropertyModel::withoutGlobalScope('tenant')
            ->with(['photos', 'documents'])
            ->find($id->getValue());

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByIdAndTenant(PropertyId $id, int $tenantId): ?Property
    {
        $model = PropertyModel::withoutGlobalScope('tenant')
            ->with(['photos', 'documents'])
            ->where('id', $id->getValue())
            ->where('tenant_id', $tenantId)
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByTenantId(int $tenantId): Collection
    {
        return PropertyModel::withoutGlobalScope('tenant')
            ->with(['photos', 'documents'])
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn (PropertyModel $m) => $this->toDomain($m));
    }

    public function findByStatus(int $tenantId, PropertyStatusEnum $status): Collection
    {
        return PropertyModel::withoutGlobalScope('tenant')
            ->with(['photos', 'documents'])
            ->where('tenant_id', $tenantId)
            ->where('status', $status->value)
            ->get()
            ->map(fn (PropertyModel $m) => $this->toDomain($m));
    }

    public function searchPublic(
        ?string $query,
        ?string $type,
        ?int $minPriceKopecks,
        ?int $maxPriceKopecks,
        ?float $minAreaSqm,
        ?int $rooms,
        ?float $lat,
        ?float $lon,
        ?int $radiusMeters,
        int $perPage,
        int $page,
    ): Collection {
        $builder = PropertyModel::withoutGlobalScope('tenant')
            ->with(['photos'])
            ->where('status', PropertyStatusEnum::Active->value);

        if ($query !== null && $query !== '') {
            $builder->where(function ($q) use ($query): void {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('address', 'like', "%{$query}%");
            });
        }

        if ($type !== null) {
            $builder->where('type', $type);
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

        $offset = ($page - 1) * $perPage;

        return $builder->orderByDesc('created_at')
            ->skip($offset)
            ->take($perPage)
            ->get()
            ->map(fn (PropertyModel $m) => $this->toDomain($m));
    }

    public function countSearchPublic(
        ?string $query,
        ?string $type,
        ?int $minPriceKopecks,
        ?int $maxPriceKopecks,
        ?float $minAreaSqm,
        ?int $rooms,
        ?float $lat,
        ?float $lon,
        ?int $radiusMeters,
    ): int {
        $builder = PropertyModel::withoutGlobalScope('tenant')
            ->where('status', PropertyStatusEnum::Active->value);

        if ($query !== null && $query !== '') {
            $builder->where(function ($q) use ($query): void {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('address', 'like', "%{$query}%");
            });
        }

        if ($type !== null) {
            $builder->where('type', $type);
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

        return $builder->count();
    }

    public function save(Property $property): void
    {
        $data = [
            'id'            => $property->getId()->getValue(),
            'tenant_id'     => $property->getTenantId(),
            'agent_id'      => $property->getAgentId()->getValue(),
            'title'         => $property->getTitle(),
            'description'   => $property->getDescription(),
            'address'       => $property->getAddress(),
            'latitude'      => $property->getCoordinates()->getLatitude(),
            'longitude'     => $property->getCoordinates()->getLongitude(),
            'type'          => $property->getType()->value,
            'price_kopecks' => $property->getPrice()->getAmountKopecks(),
            'area_sqm'      => $property->getArea()->getSquareMeters(),
            'rooms'         => $property->getRooms(),
            'floor'         => $property->getFloor(),
            'total_floors'  => $property->getTotalFloors(),
            'status'        => $property->getStatus()->value,
            'correlation_id'=> $property->getCorrelationId(),
        ];

        PropertyModel::withoutGlobalScope('tenant')
            ->updateOrCreate(['id' => $data['id']], $data);

        // Sync photos
        $photos = $property->getPhotos();
        if ($photos !== []) {
            PropertyPhotoModel::where('property_id', $data['id'])->delete();
            foreach ($photos as $idx => $photo) {
                PropertyPhotoModel::create([
                    'property_id' => $data['id'],
                    'url'         => $photo['url'],
                    'caption'     => $photo['caption'] ?? null,
                    'sort_order'  => $idx,
                ]);
            }
        }

        // Sync documents
        $documents = $property->getDocuments();
        if ($documents !== []) {
            PropertyDocumentModel::where('property_id', $data['id'])->delete();
            foreach ($documents as $doc) {
                PropertyDocumentModel::create([
                    'property_id' => $data['id'],
                    'url'         => $doc['url'],
                    'name'        => $doc['name'],
                    'doc_type'    => $doc['doc_type'] ?? null,
                ]);
            }
        }

        $this->logger->info('PropertyRepository::save', [
            'property_id'    => $data['id'],
            'status'         => $data['status'],
            'correlation_id' => $data['correlation_id'],
        ]);
    }

    public function delete(PropertyId $id): void
    {
        PropertyModel::withoutGlobalScope('tenant')
            ->where('id', $id->getValue())
            ->delete();

        $this->logger->info('PropertyRepository::delete', [
            'property_id' => $id->getValue(),
        ]);
    }

    private function toDomain(PropertyModel $model): Property
    {
        $photos    = $model->relationLoaded('photos')
            ? $model->photos->map(fn ($p) => ['url' => $p->url, 'caption' => $p->caption])->all()
            : [];

        $documents = $model->relationLoaded('documents')
            ? $model->documents->map(fn ($d) => ['url' => $d->url, 'name' => $d->name, 'doc_type' => $d->doc_type])->all()
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
            documents:     $documents,
            correlationId: $model->correlation_id ?? '',
        );
    }
}
