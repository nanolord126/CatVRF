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
            
            // Use unified PricingEngine for dynamic pricing
            $basePrice = (int) ($this->calculateTotal($dto->items) * 100); // Convert to kopecks
            
            // ML-based fraud check for payment
            try {
                $this->paymentFraudML->checkPaymentFraud(
                    tenantId: $restaurant->tenant_id,
                    userId: $dto->customerId,
                    amountKopecks: $basePrice,
                    idempotencyKey: "food_order_{$dto->restaurantId}_{$dto->customerId}_" . time(),
                    correlationId: $dto->correlationId,
                    verticalCode: 'food',
                    urgencyLevel: 'low',
                    isEmergency: false,
                );
            } catch (\RuntimeException $e) {
                $this->log->channel("audit")->warning('Food order payment blocked by ML fraud detection', [
                    'customer_id' => $dto->customerId,
                    'restaurant_id' => $dto->restaurantId,
                    'amount' => $basePrice,
                    'error' => $e->getMessage(),
                    'correlation_id' => $dto->correlationId,
                ]);
                throw new FoodPaymentFraudException($e->getMessage());
            }
            $pricingResult = $this->pricingEngine->calculatePrice(
                'food',
                $basePrice,
                [
                    'business_group_id' => $dto->businessGroupId ?? null,
                    'demand_factor' => $dto->demandFactor ?? 1.0,
                    'supply_factor' => $dto->supplyFactor ?? 1.0,
                    'timestamp' => now(),
                    'restaurant_id' => $dto->restaurantId,
                ]
            );
            $totalPrice = $pricingResult['final_price'] / 100; // Convert back to rubles
            
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
