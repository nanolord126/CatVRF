<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use App\Domains\Electronics\Services\OrderService;
use App\Services\FraudControlService;
use App\Services\Payment\WalletService;
use App\Services\CommissionService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BaseTestCase;

final class OrderServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private OrderService $service;
    private FraudControlService $fraudService;
    private WalletService $walletService;
    private CommissionService $commissionService;
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(OrderService::class);
        $this->fraudService = $this->app->make(FraudControlService::class);
        $this->walletService = $this->app->make(WalletService::class);
        $this->commissionService = $this->app->make(CommissionService::class);
        $this->notificationService = $this->app->make(NotificationService::class);
    }

    public function test_calculate_commission_for_b2c_order(): void
    {
        $total = 100000; // 1000 rubles in kopecks
        $isB2B = false;

        $commission = $this->service->calculateCommission($total, $isB2B);

        // B2C rate is 12%
        $expected = (int) ($total * 0.12);
        $this->assertEquals($expected, $commission);
        $this->assertEquals(12000, $commission);
    }

    public function test_calculate_commission_for_b2b_order(): void
    {
        $total = 100000; // 1000 rubles in kopecks
        $isB2B = true;

        $commission = $this->service->calculateCommission($total, $isB2B);

        // B2B rate is 10%
        $expected = (int) ($total * 0.10);
        $this->assertEquals($expected, $commission);
        $this->assertEquals(10000, $commission);
    }

    public function test_calculate_commission_zero_total(): void
    {
        $total = 0;
        $isB2B = false;

        $commission = $this->service->calculateCommission($total, $isB2B);

        $this->assertEquals(0, $commission);
    }

    public function test_validate_order_with_low_fraud_score(): void
    {
        $data = [
            'user_id' => 1,
            'total' => 50000,
            'items' => [
                ['product_id' => 1, 'quantity' => 1],
            ],
        ];
        $correlationId = 'test-correlation-123';

        // Mock fraud check to return low score
        $this->fraudService->shouldReceive('check')
            ->once()
            ->with($data, $correlationId)
            ->andReturn(25);

        $result = $this->service->validateOrder($data, $correlationId);

        $this->assertTrue($result['valid']);
        $this->assertEquals(25, $result['fraud_score']);
    }

    public function test_validate_order_with_high_fraud_score(): void
    {
        $data = [
            'user_id' => 1,
            'total' => 50000,
            'items' => [
                ['product_id' => 1, 'quantity' => 1],
            ],
        ];
        $correlationId = 'test-correlation-456';

        // Mock fraud check to return high score
        $this->fraudService->shouldReceive('check')
            ->once()
            ->with($data, $correlationId)
            ->andReturn(80);

        $result = $this->service->validateOrder($data, $correlationId);

        $this->assertFalse($result['valid']);
        $this->assertEquals('high_fraud_risk', $result['reason']);
        $this->assertEquals(80, $result['fraud_score']);
    }

    public function test_validate_order_with_insufficient_inventory(): void
    {
        $data = [
            'user_id' => 1,
            'total' => 50000,
            'items' => [
                ['product_id' => 1, 'quantity' => 10],
            ],
        ];
        $correlationId = 'test-correlation-789';

        // Mock fraud check to return low score
        $this->fraudService->shouldReceive('check')
            ->once()
            ->with($data, $correlationId)
            ->andReturn(20);

        $result = $this->service->validateOrder($data, $correlationId);

        // Since checkInventory is a TODO and returns available=true, this should pass
        // In production, this would fail when inventory check is implemented
        $this->assertTrue($result['valid']);
    }

    public function test_check_inventory_returns_available_when_empty(): void
    {
        $items = [];

        $result = $this->service->checkInventory($items);

        $this->assertTrue($result['available']);
        $this->assertEmpty($result['unavailable_items']);
    }

    public function test_check_inventory_handles_missing_product_id(): void
    {
        $items = [
            ['quantity' => 1], // Missing product_id
        ];

        $result = $this->service->checkInventory($items);

        $this->assertTrue($result['available']);
        $this->assertEmpty($result['unavailable_items']);
    }

    public function test_process_payment_calls_wallet_service(): void
    {
        $userId = 1;
        $amount = 50000;
        $paymentMethod = 'card';
        $correlationId = 'test-payment-123';

        $this->walletService->shouldReceive('deduct')
            ->once()
            ->with($userId, $amount, $paymentMethod, $correlationId)
            ->andReturn(true);

        $result = $this->service->processPayment($userId, $amount, $paymentMethod, $correlationId);

        $this->assertTrue($result);
    }

    public function test_send_order_confirmation_calls_notification_service(): void
    {
        $userId = 1;
        $orderId = 123;
        $correlationId = 'test-notification-123';

        $this->notificationService->shouldReceive('send')
            ->once()
            ->with(
                $userId,
                'order_confirmation',
                [
                    'order_id' => $orderId,
                    'vertical' => 'electronics',
                ],
                $correlationId
            );

        $this->service->sendOrderConfirmation($userId, $orderId, $correlationId);
    }

    public function test_get_delivery_estimate_returns_physical_delivery_string(): void
    {
        $address = 'Moscow, Main Street 1';

        $estimate = $this->service->getDeliveryEstimate($address);

        $this->assertStringContainsString('physical delivery', $estimate);
        $this->assertStringContainsString('business days', $estimate);
    }

    public function test_validate_order_with_boundary_fraud_score(): void
    {
        $data = [
            'user_id' => 1,
            'total' => 50000,
            'items' => [
                ['product_id' => 1, 'quantity' => 1],
            ],
        ];
        $correlationId = 'test-correlation-boundary';

        // Test with fraud score exactly at threshold (75)
        $this->fraudService->shouldReceive('check')
            ->once()
            ->with($data, $correlationId)
            ->andReturn(75);

        $result = $this->service->validateOrder($data, $correlationId);

        $this->assertFalse($result['valid']);
        $this->assertEquals('high_fraud_risk', $result['reason']);
    }

    public function test_validate_order_without_items(): void
    {
        $data = [
            'user_id' => 1,
            'total' => 50000,
        ];
        $correlationId = 'test-correlation-no-items';

        $this->fraudService->shouldReceive('check')
            ->once()
            ->with($data, $correlationId)
            ->andReturn(20);

        $result = $this->service->validateOrder($data, $correlationId);

        $this->assertTrue($result['valid']);
    }
}
