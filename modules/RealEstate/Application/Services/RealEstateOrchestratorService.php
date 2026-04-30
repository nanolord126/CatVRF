<?php

declare(strict_types=1);

namespace Modules\RealEstate\Application\Services;

use Modules\RealEstate\Application\DTOs\PropertyDto;
use Modules\RealEstate\Application\DTOs\PropertyBookingDto;
use Modules\RealEstate\Application\UseCases\CreatePropertyUseCase;
use Modules\RealEstate\Application\UseCases\CreatePropertyBookingUseCase;
use Modules\RealEstate\Domain\Entities\Property;
use Modules\RealEstate\Domain\Entities\PropertyBooking;
use Modules\RealEstate\Domain\Repositories\PropertyRepositoryInterface;
use Modules\RealEstate\Domain\Repositories\PropertyBookingRepositoryInterface;
use Modules\RealEstate\Domain\ValueObjects\PropertyId;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class RealEstateOrchestratorService
{
    use WithAuditLogging;

    public function __construct(
        private CreatePropertyUseCase $createPropertyUseCase,
        private CreatePropertyBookingUseCase $createBookingUseCase,
        private PropertyRepositoryInterface $propertyRepository,
        private PropertyBookingRepositoryInterface $bookingRepository,
        private readonly AuditService $auditService,
    ) {
    }

    public function createProperty(PropertyDto $dto): Property
    {
        $property = $this->createPropertyUseCase->execute($dto);
        
        $this->logCreated(
            entityType: 'Property',
            entityId: $property->id?->value ?? null,
            context: [
                'city' => $dto->city ?? null,
                'property_type' => $dto->propertyType ?? null,
            ],
            userId: null,
            tenantId: $dto->tenantId ?? null
        );
        
        return $property;
    }

    public function createBooking(PropertyBookingDto $dto): PropertyBooking
    {
        $booking = $this->createBookingUseCase->execute($dto);
        
        $this->logCreated(
            entityType: 'PropertyBooking',
            entityId: $booking->id ?? null,
            context: [
                'property_id' => $dto->propertyId ?? null,
                'check_in' => $dto->checkIn ?? null,
                'check_out' => $dto->checkOut ?? null,
            ],
            userId: $dto->guestId ?? null,
            tenantId: $dto->tenantId ?? null
        );
        
        return $booking;
    }

    public function getProperty(int $propertyId): ?Property
    {
        return $this->propertyRepository->findById(new PropertyId($propertyId));
    }

    public function getPropertyBookings(int $propertyId): array
    {
        return $this->bookingRepository->findByProperty(new PropertyId($propertyId));
    }

    public function getPropertiesByCity(string $city): array
    {
        return $this->propertyRepository->findByCity($city);
    }

    public function getAvailableProperties(): array
    {
        return $this->propertyRepository->findByStatus('available');
    }

    public function createPropertyWithBooking(PropertyDto $propertyDto, PropertyBookingDto $bookingDto): array
    {
        $property = $this->createProperty($propertyDto);

        $bookingDto->propertyId = $property->id?->value ?? 0;
        $booking = $this->createBooking($bookingDto);

        return [
            'property' => $property,
            'booking' => $booking,
        ];
    }
}
