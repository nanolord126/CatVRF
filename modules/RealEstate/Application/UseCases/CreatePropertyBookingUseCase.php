<?php

declare(strict_types=1);

namespace Modules\RealEstate\Application\UseCases;

use Modules\RealEstate\Application\DTOs\PropertyBookingDto;
use Modules\RealEstate\Domain\Entities\PropertyBooking;
use Modules\RealEstate\Domain\Events\PropertyBookingConfirmed;
use Modules\RealEstate\Domain\Repositories\PropertyBookingRepositoryInterface;
use Modules\RealEstate\Domain\Repositories\PropertyRepositoryInterface;
use Modules\RealEstate\Domain\ValueObjects\PropertyId;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event as LaravelEvent;

final readonly class CreatePropertyBookingUseCase
{
    public function __construct(
        private PropertyBookingRepositoryInterface $bookingRepository,
        private PropertyRepositoryInterface $propertyRepository,
    ) {
    }

    public function execute(PropertyBookingDto $dto): PropertyBooking
    {
        $this->validatePayload($dto);

        // Verify property exists
        $property = $this->propertyRepository->findById(new PropertyId($dto->propertyId));
        if (!$property) {
            throw new \InvalidArgumentException('Property not found');
        }

        $booking = PropertyBooking::create(
            propertyId: new PropertyId($dto->propertyId),
            userId: $dto->userId,
            checkIn: CarbonImmutable::parse($dto->checkIn),
            checkOut: CarbonImmutable::parse($dto->checkOut),
            totalPrice: $dto->totalPrice,
            metadata: $dto->metadata,
        );

        $savedBooking = $this->bookingRepository->save($booking);

        // Dispatch domain event if confirmed
        if ($dto->status === 'confirmed') {
            LaravelEvent::dispatch(new PropertyBookingConfirmed(
                booking: $savedBooking,
            ));
        }

        return $savedBooking;
    }

    private function validatePayload(PropertyBookingDto $dto): void
    {
        if ($dto->propertyId <= 0) {
            throw new \InvalidArgumentException('Property ID must be positive');
        }

        if ($dto->userId <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }

        if ($dto->totalPrice <= 0) {
            throw new \InvalidArgumentException('Total price must be positive');
        }

        $checkIn = CarbonImmutable::parse($dto->checkIn);
        $checkOut = CarbonImmutable::parse($dto->checkOut);

        if ($checkOut->isBefore($checkIn) || $checkOut->equalTo($checkIn)) {
            throw new \InvalidArgumentException('Check-out must be after check-in');
        }
    }
}
