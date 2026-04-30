<?php

declare(strict_types=1);

namespace Modules\RealEstate\Application\UseCases;

use Modules\RealEstate\Application\DTOs\PropertyDto;
use Modules\RealEstate\Domain\Entities\Property;
use Modules\RealEstate\Domain\Events\PropertyCreated;
use Modules\RealEstate\Domain\Exceptions\InvalidPropertyDataException;
use Modules\RealEstate\Domain\Repositories\PropertyRepositoryInterface;
use Modules\RealEstate\Domain\ValueObjects\Price;
use Modules\RealEstate\Domain\ValueObjects\PropertyId;
use Modules\RealEstate\Domain\ValueObjects\PropertyType;
use Illuminate\Support\Facades\Event as LaravelEvent;

final readonly class CreatePropertyUseCase
{
    public function __construct(
        private PropertyRepositoryInterface $repository,
    ) {
    }

    public function execute(PropertyDto $dto): Property
    {
        $this->validatePayload($dto);

        $property = Property::create(
            tenantId: $dto->tenantId,
            type: new PropertyType($dto->type),
            title: $dto->title,
            description: $dto->description,
            address: $dto->address,
            city: $dto->city,
            country: $dto->country,
            price: new Price($dto->price, $dto->currency),
            area: $dto->area,
            bedrooms: $dto->bedrooms,
            bathrooms: $dto->bathrooms,
            status: $dto->status,
            amenities: $dto->amenities,
            metadata: $dto->metadata,
        );

        $savedProperty = $this->repository->save($property);

        // Dispatch domain event
        LaravelEvent::dispatch(new PropertyCreated(
            property: $savedProperty,
        ));

        return $savedProperty;
    }

    private function validatePayload(PropertyDto $dto): void
    {
        if (empty($dto->title)) {
            throw new InvalidPropertyDataException('Property title is required');
        }

        if (empty($dto->address)) {
            throw new InvalidPropertyDataException('Property address is required');
        }

        if (empty($dto->city)) {
            throw new InvalidPropertyDataException('City is required');
        }

        if ($dto->area <= 0) {
            throw new InvalidPropertyDataException('Area must be positive');
        }

        if ($dto->price <= 0) {
            throw new InvalidPropertyDataException('Price must be positive');
        }

        if ($dto->bedrooms < 0) {
            throw new InvalidPropertyDataException('Bedrooms cannot be negative');
        }

        if ($dto->bathrooms < 0) {
            throw new InvalidPropertyDataException('Bathrooms cannot be negative');
        }
    }
}
