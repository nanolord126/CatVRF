<?php

declare(strict_types=1);

namespace App\Domains\Food\Application\B2B\UseCases;

use App\Domains\Food\Application\B2B\DataTransferObjects\RestaurantDto;
use App\Domains\Food\Domain\Entities\Restaurant;
use App\Domains\Food\Domain\Repositories\RestaurantRepositoryInterface;
use App\Domains\Food\Domain\ValueObjects\RestaurantStatus;
use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Log\LoggerInterface;

final readonly class ManageRestaurantUseCase
{
    public function __construct(
        private RestaurantRepositoryInterface $restaurantRepository,
        private LoggerInterface $logger,
        private Dispatcher $eventDispatcher
    ) {

    }

    public function createRestaurant(RestaurantDto $dto): Restaurant
    {
        $this->logger->info('Attempting to create a new restaurant.', [
            'name' => $dto->name,
            'tenant_id' => $dto->tenantId->toString(),
            'correlation_id' => $dto->correlationId?->toString(),
        ]);

        $restaurant = new Restaurant(
            id: $dto->id,
            tenantId: $dto->tenantId,
            name: $dto->name,
            description: $dto->description,
            address: $dto->address,
            contact: $dto->contact,
            status: RestaurantStatus::IN_REVIEW,
            schedule: $dto->schedule,
            menuSections: collect(),
            correlationId: $dto->correlationId
        );

        $this->restaurantRepository->save($restaurant);

        $this->logger->info('Successfully created a new restaurant.', [
            'restaurant_id' => $restaurant->id->toString(),
            'correlation_id' => $dto->correlationId?->toString(),
        ]);

        // Dispatch event if needed
        // $this->eventDispatcher->dispatch(new RestaurantCreated($restaurant->id));

        return $restaurant;
    }

    public function updateRestaurant(Uuid $id, RestaurantDto $dto): ?Restaurant
    {
        $this->logger->info('Attempting to update a restaurant.', [
            'restaurant_id' => $id->toString(),
            'correlation_id' => $dto->correlationId?->toString(),
        ]);

        $restaurant = $this->restaurantRepository->findById($id);

        if (!$restaurant) {
            $this->logger->warning('Restaurant not found for update.', [
                'restaurant_id' => $id->toString(),
                'correlation_id' => $dto->correlationId?->toString(),
            ]);
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        $restaurant->name = $dto->name;
        $restaurant->description = $dto->description;
        $restaurant->address = $dto->address;
        $restaurant->contact = $dto->contact;
        $restaurant->schedule = $dto->schedule;
        $restaurant->correlationId = $dto->correlationId;

        $this->restaurantRepository->save($restaurant);

        $this->logger->info('Successfully updated restaurant.', [
            'restaurant_id' => $id->toString(),
            'correlation_id' => $dto->correlationId?->toString(),
        ]);

        return $restaurant;
    }

    public function openRestaurant(Uuid $id, ?Uuid $correlationId = null): bool
    {
        $restaurant = $this->restaurantRepository->findById($id);

        if (!$restaurant) {
            $this->logger->warning('Restaurant not found to open.', ['restaurant_id' => $id->toString()]);
            return false;
        }

        $restaurant->open();
        $this->restaurantRepository->save($restaurant);

        $this->logger->info('Restaurant opened.', [
            'restaurant_id' => $id->toString(),
            'correlation_id' => $correlationId?->toString(),
        ]);

        return true;
    }

    public function closeRestaurant(Uuid $id, ?Uuid $correlationId = null): bool
    {
        $restaurant = $this->restaurantRepository->findById($id);

        if (!$restaurant) {
            $this->logger->warning('Restaurant not found to close.', ['restaurant_id' => $id->toString()]);
            return false;
        }

        $restaurant->close();
        $this->restaurantRepository->save($restaurant);

        $this->logger->info('Restaurant closed.', [
            'restaurant_id' => $id->toString(),
            'correlation_id' => $correlationId?->toString(),
        ]);

        return true;
    }
}
