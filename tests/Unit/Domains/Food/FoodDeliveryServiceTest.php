<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Food;

use Tests\BaseTestCase;
use App\Domains\Food\Models\DeliveryOrder;
use App\Domains\Food\Models\FoodOrder;
use App\Domains\Food\Services\FoodDeliveryService;
use App\Domains\Food\Infrastructure\Gateways\FakeDeliveryServiceGateway;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Support\Str;
use Mockery;

final class FoodDeliveryServiceTest extends BaseTestCase
{
    private FoodDeliveryService $service;
    private FraudControlService $fraud;
    private AuditService $audit;
    private FakeDeliveryServiceGateway $deliveryGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = Mockery::mock(FraudControlService::class);
        $this->audit = Mockery::mock(AuditService::class);
        $this->deliveryGateway = Mockery::mock(FakeDeliveryServiceGateway::class);

        $this->service = new FoodDeliveryService(
            $this->fraud,
            $this->audit,
            $this->deliveryGateway,
            $this->app->make(\Psr\Log\LoggerInterface::class)
        );
    }

    public function test_create_delivery_for_order(): void
    {
        $order = FoodOrder::factory()->create([
            'delivery_address' => 'Test Address 123',
            'delivery_lat' => 55.7558,
            'delivery_lon' => 37.6173,
        ]);

        $this->fraud->shouldReceive('check')
            ->once()
            ->with(
                \Mockery::type('int'),
                'food_delivery_create',
                \Mockery::type('float'),
                \Mockery::type('string')
            );

        $this->deliveryGateway->shouldReceive('scheduleDelivery')
            ->once()
            ->andReturn([
                'success' => true,
                'delivery_id' => (string) Str::uuid(),
                'estimated_time_minutes' => 30,
                'status' => 'scheduled',
            ]);

        $this->audit->shouldReceive('log')
            ->once()
            ->with(
                'created',
                DeliveryOrder::class,
                \Mockery::type('int'),
                [],
                \Mockery::type('array'),
                \Mockery::type('string')
            );

        $delivery = $this->service->createDeliveryForOrder($order);

        $this->assertInstanceOf(DeliveryOrder::class, $delivery);
        $this->assertEquals($order->id, $delivery->food_order_id);
        $this->assertEquals('pending', $delivery->status);
        $this->assertEquals('Test Address 123', $delivery->customer_address);
        $this->assertEquals(30, $delivery->eta_minutes);
    }

    public function test_update_delivery_status(): void
    {
        $order = FoodOrder::factory()->create();
        $delivery = DeliveryOrder::factory()->create([
            'food_order_id' => $order->id,
            'status' => DeliveryOrder::STATUS_PENDING,
        ]);

        $this->fraud->shouldReceive('check')
            ->once()
            ->with(
                \Mockery::type('int'),
                'food_delivery_update',
                0,
                \Mockery::type('string')
            );

        $this->audit->shouldReceive('log')
            ->once()
            ->with(
                'updated',
                DeliveryOrder::class,
                $delivery->id,
                ['status' => DeliveryOrder::STATUS_PENDING],
                ['status' => DeliveryOrder::STATUS_ON_WAY],
                \Mockery::type('string')
            );

        $updated = $this->service->updateDeliveryStatus(
            $delivery,
            DeliveryOrder::STATUS_ON_WAY
        );

        $this->assertEquals(DeliveryOrder::STATUS_ON_WAY, $updated->status);
    }

    public function test_update_delivery_status_to_delivered_sets_timestamp(): void
    {
        $order = FoodOrder::factory()->create();
        $delivery = DeliveryOrder::factory()->create([
            'food_order_id' => $order->id,
            'status' => DeliveryOrder::STATUS_ON_WAY,
            'delivered_at' => null,
        ]);

        $this->fraud->shouldReceive('check')->once();
        $this->audit->shouldReceive('log')->once();

        $updated = $this->service->updateDeliveryStatus(
            $delivery,
            DeliveryOrder::STATUS_DELIVERED
        );

        $this->assertEquals(DeliveryOrder::STATUS_DELIVERED, $updated->status);
        $this->assertNotNull($updated->delivered_at);
    }

    public function test_sync_delivery_status(): void
    {
        $order = FoodOrder::factory()->create();
        $delivery = DeliveryOrder::factory()->create([
            'food_order_id' => $order->id,
        ]);

        $this->deliveryGateway->shouldReceive('getDeliveryStatus')
            ->once()
            ->with(
                $delivery->uuid,
                \Mockery::type('string')
            )
            ->andReturn([
                'delivery_id' => $delivery->uuid,
                'status' => 'in_transit',
                'updated_at' => now()->toIso8601String(),
            ]);

        $status = $this->service->syncDeliveryStatus($delivery);

        $this->assertEquals('in_transit', $status['status']);
        $this->assertEquals($delivery->uuid, $status['delivery_id']);
    }

    public function test_cancel_delivery(): void
    {
        $order = FoodOrder::factory()->create();
        $delivery = DeliveryOrder::factory()->create([
            'food_order_id' => $order->id,
            'status' => DeliveryOrder::STATUS_PENDING,
            'cancelled_at' => null,
        ]);

        $this->fraud->shouldReceive('check')
            ->once()
            ->with(
                \Mockery::type('int'),
                'food_delivery_cancel',
                0,
                \Mockery::type('string')
            );

        $this->audit->shouldReceive('log')
            ->once()
            ->with(
                'cancelled',
                DeliveryOrder::class,
                $delivery->id,
                [],
                ['cancellation_reason' => 'Customer request'],
                \Mockery::type('string')
            );

        $cancelled = $this->service->cancelDelivery(
            $delivery,
            'Customer request'
        );

        $this->assertEquals(DeliveryOrder::STATUS_CANCELLED, $cancelled->status);
        $this->assertEquals('Customer request', $cancelled->cancellation_reason);
        $this->assertNotNull($cancelled->cancelled_at);
    }

    public function test_delivery_order_status_constants(): void
    {
        $this->assertEquals('pending', DeliveryOrder::STATUS_PENDING);
        $this->assertEquals('accepted', DeliveryOrder::STATUS_ACCEPTED);
        $this->assertEquals('on_way', DeliveryOrder::STATUS_ON_WAY);
        $this->assertEquals('delivered', DeliveryOrder::STATUS_DELIVERED);
        $this->assertEquals('cancelled', DeliveryOrder::STATUS_CANCELLED);
    }

    public function test_delivery_order_helper_methods(): void
    {
        $delivery = new DeliveryOrder();

        $delivery->status = DeliveryOrder::STATUS_PENDING;
        $this->assertTrue($delivery->isPending());
        $this->assertFalse($delivery->isOnWay());
        $this->assertFalse($delivery->isDelivered());
        $this->assertFalse($delivery->isCancelled());

        $delivery->status = DeliveryOrder::STATUS_ON_WAY;
        $this->assertFalse($delivery->isPending());
        $this->assertTrue($delivery->isOnWay());
        $this->assertFalse($delivery->isDelivered());
        $this->assertFalse($delivery->isCancelled());

        $delivery->status = DeliveryOrder::STATUS_DELIVERED;
        $this->assertFalse($delivery->isPending());
        $this->assertFalse($delivery->isOnWay());
        $this->assertTrue($delivery->isDelivered());
        $this->assertFalse($delivery->isCancelled());

        $delivery->status = DeliveryOrder::STATUS_CANCELLED;
        $this->assertFalse($delivery->isPending());
        $this->assertFalse($delivery->isOnWay());
        $this->assertFalse($delivery->isDelivered());
        $this->assertTrue($delivery->isCancelled());
    }
}
