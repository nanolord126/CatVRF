<?php
declare(strict_types=1);

namespace App\Domains\Food\Services;

use App\Domains\Food\Models\FoodOrder;
use App\Domains\Food\Models\Restaurant;
use App\Domains\Food\Models\Dish;
use App\Domains\Food\DTOs\CreateFoodOrderDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

final readonly class FoodOrderingService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $log,
        private FoodDeliveryService $deliveryService
    ) {}

    public function placeOrder(CreateFoodOrderDto $dto): FoodOrder
    {
        $this->fraud->check(
            userId: $dto->customerId,
            operationType: 'place_food_order',
            amount: 0,
            correlationId: $dto->correlationId
        );

        return $this->db->transaction(function () use ($dto): FoodOrder {
            $restaurant = Restaurant::findOrFail($dto->restaurantId);
            
            $totalPrice = $this->calculateTotal($dto->items);
            
            $order = FoodOrder::create([
                "restaurant_id" => $restaurant->id,
                "customer_id" => $dto->customerId,
                "items" => $dto->items,
                "total_price" => $totalPrice,
                "status" => "pending",
                "delivery_address" => $dto->deliveryAddress,
                "delivery_lat" => $dto->deliveryLat,
                "delivery_lon" => $dto->deliveryLon,
                "special_instructions" => $dto->specialInstructions,
                "payment_status" => "unpaid",
                "correlation_id" => $dto->correlationId,
            ]);

            // Dispatch event for Delivery Integration to assign a courier
            event(new \App\Events\FoodOrderPlacedEvent($order));

            // Automatically create delivery record for the order
            if ($dto->deliveryAddress) {
                try {
                    $this->deliveryService->createDeliveryForOrder($order);
                    $this->log->channel("audit")->info("Food delivery created for order", [
                        "order_id" => $order->id,
                        "delivery_address" => $dto->deliveryAddress,
                        "correlation_id" => $dto->correlationId,
                    ]);
                } catch (\Throwable $e) {
                    $this->log->channel("audit")->error("Failed to create delivery for food order", [
                        "order_id" => $order->id,
                        "error" => $e->getMessage(),
                        "correlation_id" => $dto->correlationId,
                    ]);
                    // Don't fail the order if delivery creation fails
                }
            }

            $this->audit->log(
                'created',
                FoodOrder::class,
                $order->id,
                [],
                $order->toArray(),
                $dto->correlationId
            );

            $this->log->channel("audit")->info("Food order successfully placed", [
                "order_id" => $order->id,
                "total_price" => $totalPrice,
                "correlation_id" => $dto->correlationId,
            ]);

            return $order;
        });
    }

    private function calculateTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $dish = Dish::findOrFail($item["dish_id"]);
            $quantity = (int) ($item["quantity"] ?? 1);
            $total += ($dish->price * $quantity);
            // Ignore modifiers pricing for now, but can be added here
        }
        return $total;
    }
}
