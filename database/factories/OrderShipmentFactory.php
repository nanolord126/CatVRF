<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\OrderShipment;
use App\Domains\Logistics\Models\PickupPoint;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderShipment>
 */
final class OrderShipmentFactory extends Factory
{
    protected $model = OrderShipment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $fulfillmentType = fake()->randomElement(['courier', 'pickup_point', 'taxi']);

        return [
            'uuid' => fake()->uuid(),
            'tenant_id' => 1,
            'order_id' => Order::factory(),
            'courier_id' => $fulfillmentType === 'courier' || $fulfillmentType === 'taxi'
                ? Courier::factory()
                : null,
            'pickup_point_id' => $fulfillmentType === 'pickup_point'
                ? PickupPoint::factory()
                : null,
            'fulfillment_type' => $fulfillmentType,
            'fulfillment_id' => null, // Set in afterCreating
            'status' => fake()->randomElement([
                'pending', 'assigned', 'picked', 'in_transit', 'delivered', 'issued_at_pvz',
            ]),
            'eta_minutes' => fake()->numberBetween(15, 120),
            'route_polyline' => null,
            'distance_km' => fake()->randomFloat(1, 0.5, 50),
            'assigned_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'picked_at' => fake()->optional(0.7)->dateTimeBetween('assigned_at', 'now'),
            'delivered_at' => fake()->optional(0.5)->dateTimeBetween('picked_at', 'now'),
            'issued_at_pvz' => fake()->optional(0.3)->dateTimeBetween('assigned_at', 'now'),
            'qr_code' => null,
            'pickup_code' => fake()->optional()->numerify('####'),
            'metadata' => [
                'delivery_notes' => fake()->optional()->sentence(),
                'special_instructions' => fake()->optional()->sentence(),
            ],
            'correlation_id' => fake()->uuid(),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (OrderShipment $shipment) {
            // Set polymorphic fulfillment_id
            if ($shipment->fulfillment_type === 'courier' && $shipment->courier_id) {
                $shipment->update(['fulfillment_id' => $shipment->courier_id]);
            } elseif ($shipment->fulfillment_type === 'pickup_point' && $shipment->pickup_point_id) {
                $shipment->update(['fulfillment_id' => $shipment->pickup_point_id]);
            }

            // Generate QR code if not set
            if (! $shipment->qr_code) {
                $shipment->generateQrCode();
            }
        });
    }

    /**
     * State for courier-based shipments
     */
    public function courierBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'fulfillment_type' => 'courier',
            'courier_id' => Courier::factory(),
            'pickup_point_id' => null,
        ]);
    }

    /**
     * State for PVZ-based shipments
     */
    public function pvzBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'fulfillment_type' => 'pickup_point',
            'courier_id' => null,
            'pickup_point_id' => PickupPoint::factory(),
        ]);
    }

    /**
     * State for taxi-based shipments (hybrid)
     */
    public function taxiBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'fulfillment_type' => 'taxi',
            'courier_id' => Courier::factory()->taxiDriver(),
            'pickup_point_id' => null,
        ]);
    }

    /**
     * State for pending shipments
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'assigned_at' => null,
            'picked_at' => null,
            'delivered_at' => null,
        ]);
    }

    /**
     * State for delivered shipments
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'assigned_at' => fake()->dateTimeBetween('-2 hours', '-1 hour'),
            'picked_at' => fake()->dateTimeBetween('-1 hour', '-30 minutes'),
            'delivered_at' => fake()->dateTimeBetween('-30 minutes', 'now'),
        ]);
    }

    /**
     * State for issued at PVZ
     */
    public function issuedAtPvz(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'issued_at_pvz',
            'fulfillment_type' => 'pickup_point',
            'pickup_point_id' => PickupPoint::factory(),
            'issued_at_pvz' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}
