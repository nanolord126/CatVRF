<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use App\Domains\Electronics\DTOs\SplitPaymentRequestDto;
use App\Domains\Electronics\DTOs\SplitPaymentResponseDto;
use App\Domains\Electronics\Services\ElectronicsWalletService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Services\PaymentService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ElectronicsWalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private ElectronicsWalletService $service;
    private FraudControlService $fraud;
    private WalletService $wallet;
    private PaymentService $payment;
    private Cache $cache;
    private DatabaseManager $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->createMock(FraudControlService::class);
        $this->fraud->method('check')->willReturn(null);

        $this->wallet = $this->createMock(WalletService::class);
        $this->payment = $this->createMock(PaymentService::class);
        $this->cache = app(Cache::class);
        $this->db = app(DatabaseManager::class);

        $this->service = new ElectronicsWalletService(
            $this->fraud,
            $this->wallet,
            $this->payment,
            $this->cache,
            $this->db,
            app('log'),
        );
    }

    #[Test]
    public function it_processes_split_payment_successfully(): void
    {
        $userId = 1;
        $orderId = 100;
        $correlationId = 'test-split-123';

        $this->wallet->expects($this->once())
            ->method('debit')
            ->willReturn(['transaction_id' => 'wallet_tx_123']);

        $this->payment->expects($this->once())
            ->method('initPayment')
            ->willReturn([
                'transaction_id' => 'card_tx_456',
                'metadata' => [],
            ]);

        $dto = new SplitPaymentRequestDto(
            orderId: $orderId,
            userId: $userId,
            correlationId: $correlationId,
            totalAmountKopecks: 15000000,
            paymentSources: [
                [
                    'source' => 'wallet',
                    'amount_kopecks' => 5000000,
                    'metadata' => [],
                ],
                [
                    'source' => 'card',
                    'amount_kopecks' => 10000000,
                    'metadata' => [],
                ],
            ],
            useEscrow: false,
            escrowReleaseDays: 0,
            metadata: [],
            idempotencyKey: null,
        );

        $result = $this->service->processSplitPayment($dto);

        $this->assertInstanceOf(SplitPaymentResponseDto::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals($correlationId, $result->correlationId);
        $this->assertEquals(15000000, $result->totalAmountKopecks);
        $this->assertFalse($result->escrowEnabled);
        $this->assertCount(2, $result->paymentResults);
    }

    #[Test]
    public function it_validates_payment_sources_total(): void
    {
        $dto = new SplitPaymentRequestDto(
            orderId: 100,
            userId: 1,
            correlationId: 'test-456',
            totalAmountKopecks: 15000000,
            paymentSources: [
                [
                    'source' => 'wallet',
                    'amount_kopecks' => 5000000,
                    'metadata' => [],
                ],
                [
                    'source' => 'card',
                    'amount_kopecks' => 5000000,
                    'metadata' => [],
                ],
            ],
            useEscrow: false,
            escrowReleaseDays: 0,
            metadata: [],
            idempotencyKey: null,
        );

        $this->assertFalse($dto->validatePaymentSources());
    }

    #[Test]
    public function it_creates_escrow_hold_when_enabled(): void
    {
        $userId = 1;
        $orderId = 101;
        $correlationId = 'test-escrow-789';

        $this->wallet->expects($this->once())
            ->method('debit')
            ->willReturn(['transaction_id' => 'wallet_tx_789']);

        $this->payment->expects($this->never())
            ->method('initPayment');

        $dto = new SplitPaymentRequestDto(
            orderId: $orderId,
            userId: $userId,
            correlationId: $correlationId,
            totalAmountKopecks: 5000000,
            paymentSources: [
                [
                    'source' => 'wallet',
                    'amount_kopecks' => 5000000,
                    'metadata' => [],
                ],
            ],
            useEscrow: true,
            escrowReleaseDays: 7,
            metadata: [],
            idempotencyKey: null,
        );

        $result = $this->service->processSplitPayment($dto);

        $this->assertTrue($result->success);
        $this->assertTrue($result->escrowEnabled);
        $this->assertNotNull($result->escrowReleaseDate);

        $this->assertDatabaseHas('electronics_escrow_holds', [
            'order_id' => $orderId,
            'user_id' => $userId,
            'status' => 'held',
        ]);
    }

    #[Test]
    public function it_rolls_back_payment_on_failure(): void
    {
        $userId = 1;
        $orderId = 102;
        $correlationId = 'test-rollback-101';

        $this->wallet->expects($this->once())
            ->method('debit')
            ->willReturn(['transaction_id' => 'wallet_tx_101', 'metadata' => ['wallet_id' => 1]]);

        $this->wallet->expects($this->once())
            ->method('credit')
            ->willReturn(null);

        $this->payment->expects($this->once())
            ->method('initPayment')
            ->willThrowException(new \Exception('Payment gateway error'));

        $dto = new SplitPaymentRequestDto(
            orderId: $orderId,
            userId: $userId,
            correlationId: $correlationId,
            totalAmountKopecks: 15000000,
            paymentSources: [
                [
                    'source' => 'wallet',
                    'amount_kopecks' => 5000000,
                    'metadata' => [],
                ],
                [
                    'source' => 'card',
                    'amount_kopecks' => 10000000,
                    'metadata' => [],
                ],
            ],
            useEscrow: false,
            escrowReleaseDays: 0,
            metadata: [],
            idempotencyKey: null,
        );

        $result = $this->service->processSplitPayment($dto);

        $this->assertFalse($result->success);
        $this->assertEquals('One or more payment sources failed', $result->failureReason);
    }

    #[Test]
    public function it_calculates_b2b_commission_correctly(): void
    {
        $userId = 1;

        $this->db->table('business_groups')->insert([
            'owner_id' => $userId,
            'tenant_id' => tenant()->id,
            'name' => 'Test Business',
            'inn' => '1234567890',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $commissionRate = $this->service->getCommissionRate($userId, 500000000);

        $this->assertEquals(0.12, $commissionRate);
    }

    #[Test]
    public function it_calculates_b2c_commission_correctly(): void
    {
        $userId = 1;

        $commissionRate = $this->service->getCommissionRate($userId, 10000000);

        $this->assertEquals(0.14, $commissionRate);
    }

    #[Test]
    public function it_releases_escrow_successfully(): void
    {
        $userId = 1;
        $orderId = 103;
        $paymentId = 'escrow_test_123';

        $this->db->table('electronics_escrow_holds')->insert([
            'order_id' => $orderId,
            'user_id' => $userId,
            'tenant_id' => tenant()->id,
            'payment_id' => $paymentId,
            'amount_kopecks' => 5000000,
            'status' => 'held',
            'release_date' => now()->addDays(7)->toIso8601String(),
            'correlation_id' => 'test-123',
            'metadata' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->wallet->expects($this->exactly(2))
            ->method('credit')
            ->willReturn(null);

        $result = $this->service->releaseEscrow($paymentId, 'Order completed successfully', 'test-release-456');

        $this->assertTrue($result);

        $this->assertDatabaseHas('electronics_escrow_holds', [
            'payment_id' => $paymentId,
            'status' => 'released',
            'release_reason' => 'Order completed successfully',
        ]);
    }

    #[Test]
    public function it_saves_split_payment_record(): void
    {
        $userId = 1;
        $orderId = 104;
        $correlationId = 'test-save-789';

        $this->wallet->expects($this->once())
            ->method('debit')
            ->willReturn(['transaction_id' => 'wallet_tx_789']);

        $dto = new SplitPaymentRequestDto(
            orderId: $orderId,
            userId: $userId,
            correlationId: $correlationId,
            totalAmountKopecks: 10000000,
            paymentSources: [
                [
                    'source' => 'wallet',
                    'amount_kopecks' => 10000000,
                    'metadata' => [],
                ],
            ],
            useEscrow: false,
            escrowReleaseDays: 0,
            metadata: [],
            idempotencyKey: null,
        );

        $this->service->processSplitPayment($dto);

        $this->assertDatabaseHas('electronics_split_payments', [
            'order_id' => $orderId,
            'user_id' => $userId,
            'total_amount_kopecks' => 10000000,
            'escrow_enabled' => false,
        ]);
    }

    #[Test]
    public function it_returns_cached_result(): void
    {
        $userId = 1;
        $orderId = 105;
        $correlationId = 'test-cache-101';
        $idempotencyKey = 'cache_key_123';

        $this->wallet->expects($this->once())
            ->method('debit')
            ->willReturn(['transaction_id' => 'wallet_tx_101']);

        $dto = new SplitPaymentRequestDto(
            orderId: $orderId,
            userId: $userId,
            correlationId: $correlationId,
            totalAmountKopecks: 5000000,
            paymentSources: [
                [
                    'source' => 'wallet',
                    'amount_kopecks' => 5000000,
                    'metadata' => [],
                ],
            ],
            useEscrow: false,
            escrowReleaseDays: 0,
            metadata: [],
            idempotencyKey: $idempotencyKey,
        );

        $firstResult = $this->service->processSplitPayment($dto);
        $secondResult = $this->service->processSplitPayment($dto);

        $this->assertEquals($firstResult->paymentId, $secondResult->paymentId);
    }

    private function getCommissionRate(int $userId, int $amountKopecks): float
    {
        $isB2B = $this->db->table('business_groups')
            ->where('owner_id', $userId)
            ->exists();

        if ($isB2B) {
            $amountRubles = $amountKopecks / 100;
            return match (true) {
                $amountRubles >= 1000000 => 0.08,
                $amountRubles >= 500000 => 0.10,
                default => 0.12,
            };
        }

        return 0.14;
    }
}
