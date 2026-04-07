<?php

declare(strict_types=1);

namespace App\Domains\Food\Application\B2C\UseCases;

use App\Domains\Food\Application\B2C\DataTransferObjects\CartDto;
use App\Domains\Food\Domain\Entities\Order;
use App\Domains\Food\Domain\Entities\OrderItem;
use App\Domains\Food\Domain\Repositories\OrderRepositoryInterface;
use App\Domains\Food\Domain\Repositories\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObjects\TenantId;
use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Log\LoggerInterface;

final readonly class PlaceOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
        private LoggerInterface $logger,
        private Dispatcher $eventDispatcher
    ) {

    }

    public function placeOrder(CartDto $cartDto, TenantId $tenantId, ?Uuid $correlationId = null): Order
    {
        $this->logger->info('Attempting to place a new order.', [
            'client_id' => $cartDto->clientId->toString(),
            'restaurant_id' => $cartDto->restaurantId->toString(),
            'correlation_id' => $correlationId?->toString(),
        ]);

        $restaurant = $this->restaurantRepository->findById($cartDto->restaurantId);

        if (!$restaurant || !$restaurant->status->canAcceptOrders()) {
            $this->logger->error('Restaurant is not available for ordering.', [
                'restaurant_id' => $cartDto->restaurantId->toString(),
                'correlation_id' => $correlationId?->toString(),
            ]);
            // In a real app, throw a custom exception
            throw new \DomainException('Restaurant is not accepting orders at the moment.');
        }

        $orderItems = $cartDto->items->map(function ($itemDto) use ($restaurant) {
            $dish = $restaurant->menuSections
                ->flatMap(fn ($section) => $section->dishes)
                ->firstWhere('id', $itemDto->dishId);

            if (!$dish || !$dish->isAvailable) {
                throw new \DomainException("Dish {$itemDto->dishId} is not available.");
            }

            $modifiers = $dish->modifiers->whereIn('id', $itemDto->modifierIds);

            return new OrderItem(
                id: Uuid::create(),
                dishId: $dish->id,
                dishName: $dish->name,
                quantity: $itemDto->quantity,
                unitPrice: $dish->price,
                modifiers: $modifiers
            );
        });

        $order = Order::place(
            id: $cartDto->id,
            tenantId: $tenantId,
            restaurantId: $cartDto->restaurantId,
            clientId: $cartDto->clientId,
            items: $orderItems,
            correlationId: $correlationId
        );

        $this->orderRepository->save($order);

        foreach ($order->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        $this->logger->info('Successfully placed a new order.', [
            'order_id' => $order->id->toString(),
            'correlation_id' => $correlationId?->toString(),
        ]);

        return $order;
    }
}
