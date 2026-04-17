<?php declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Jobs\ProcessB2BOrderJob;
use App\Services\Wallet\WalletService;
use App\Services\CommissionService;
use App\Services\NotificationService;
use App\Services\FraudControlService;

final class ProcessB2BOrderJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_b2b_order_job(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'beauty',
            'status' => 'pending',
            'is_b2b' => true,
            'inn' => '123456789012',
            'business_card_id' => 'BC-test123',
            'total' => 1000000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 10,
            'unit_price' => 100000,
        ]);

        $job = new ProcessB2BOrderJob(
            orderId: $order->id,
            tenantId: $order->tenant_id,
            correlationId: Str::uuid()->toString(),
        );

        $this->app->instance(WalletService::class, $this->createMockWalletService());
        $this->app->instance(CommissionService::class, $this->createMockCommissionService());
        $this->app->instance(NotificationService::class, $this->createMockNotificationService());
        $this->app->instance(FraudControlService::class, $this->createMockFraudService());

        $job->handle();

        $order->refresh();

        $this->assertEquals('confirmed', $order->status);
    }

    private function createMockWalletService(): WalletService
    {
        $mock = $this->createMock(WalletService::class);
        $mock->method('debit')->willReturn(null);
        $mock->method('credit')->willReturn(null);
        return $mock;
    }

    private function createMockCommissionService(): CommissionService
    {
        $mock = $this->createMock(CommissionService::class);
        $mock->method('calculateCommission')->willReturn(120000);
        return $mock;
    }

    private function createMockNotificationService(): NotificationService
    {
        $mock = $this->createMock(NotificationService::class);
        $mock->method('send')->willReturn(true);
        return $mock;
    }

    private function createMockFraudService(): FraudControlService
    {
        $mock = $this->createMock(FraudControlService::class);
        $mock->method('check')->willReturn([
            'score' => 10,
            'decision' => 'allow',
        ]);
        return $mock;
    }
}
