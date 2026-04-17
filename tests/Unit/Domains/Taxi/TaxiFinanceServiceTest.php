<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Taxi;

use App\Domains\Taxi\Services\TaxiFinanceService;
use App\Domains\Taxi\Models\TaxiTransaction;
use App\Domains\Taxi\Models\TaxiDriverWallet;
use App\Domains\Taxi\Models\TaxiWithdrawal;
use App\Domains\Taxi\Models\TaxiRide;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Payment\PaymentService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaxiFinanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxiFinanceService $financeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->financeService = new TaxiFinanceService(
            $this->app->make(FraudControlService::class),
            $this->app->make(AuditService::class),
            $this->app->make(PaymentService::class),
            $this->app->make(DatabaseManager::class),
        );
    }

    public function test_process_payment_creates_transaction(): void
    {
        $ride = TaxiRide::factory()->create([
            'total_price' => 50000, // 500 RUB
            'status' => TaxiRide::STATUS_COMPLETED,
        ]);

        $transaction = $this->financeService->processPayment(
            rideId: $ride->id,
            amountKopeki: 50000,
            paymentMethod: 'card',
            correlationId: 'test-correlation',
        );

        $this->assertInstanceOf(TaxiTransaction::class, $transaction);
        $this->assertEquals(TaxiTransaction::TYPE_PAYMENT, $transaction->type);
        $this->assertEquals(50000, $transaction->amount_kopeki);
        $this->assertEquals(TaxiTransaction::STATUS_COMPLETED, $transaction->status);
    }

    public function test_process_refund_creates_refund_transaction(): void
    {
        $transaction = TaxiTransaction::factory()->create([
            'type' => TaxiTransaction::TYPE_PAYMENT,
            'amount_kopeki' => 50000,
            'status' => TaxiTransaction::STATUS_COMPLETED,
        ]);

        $refund = $this->financeService->processRefund(
            transactionId: $transaction->id,
            refundAmountKopeki: 50000,
            reason: 'Customer request',
            correlationId: 'test-correlation',
        );

        $this->assertInstanceOf(TaxiTransaction::class, $refund);
        $this->assertEquals(TaxiTransaction::TYPE_REFUND, $refund->type);
        $this->assertEquals(50000, $refund->refunded_amount_kopeki);
    }

    public function test_credit_driver_wallet_increases_balance(): void
    {
        $wallet = TaxiDriverWallet::factory()->create([
            'balance_kopeki' => 10000,
        ]);

        $this->financeService->creditDriverWallet(
            driverId: $wallet->driver_id,
            amountKopeki: 5000,
            correlationId: 'test-correlation',
        );

        $wallet->refresh();
        $this->assertEquals(15000, $wallet->balance_kopeki);
        $this->assertEquals(5000, $wallet->total_earned_kopeki);
    }

    public function test_debit_driver_wallet_decreases_balance(): void
    {
        $wallet = TaxiDriverWallet::factory()->create([
            'balance_kopeki' => 10000,
        ]);

        $this->financeService->debitDriverWallet(
            driverId: $wallet->driver_id,
            amountKopeki: 3000,
            correlationId: 'test-correlation',
        );

        $wallet->refresh();
        $this->assertEquals(7000, $wallet->balance_kopeki);
        $this->assertEquals(3000, $wallet->total_withdrawn_kopeki);
    }

    public function test_create_withdrawal_freezes_amount(): void
    {
        $wallet = TaxiDriverWallet::factory()->create([
            'balance_kopeki' => 50000,
        ]);

        $withdrawal = $this->financeService->createWithdrawal(
            driverId: $wallet->driver_id,
            amountKopeki: 10000,
            bankDetails: [
                'bank_name' => 'Test Bank',
                'bank_account_number' => '1234567890',
                'bank_account_holder' => 'Test Driver',
            ],
            correlationId: 'test-correlation',
        );

        $this->assertInstanceOf(TaxiWithdrawal::class, $withdrawal);
        $this->assertEquals(TaxiWithdrawal::STATUS_PENDING, $withdrawal->status);
        
        $wallet->refresh();
        $this->assertEquals(10000, $wallet->frozen_kopeki);
    }

    public function test_get_driver_financial_summary(): void
    {
        $wallet = TaxiDriverWallet::factory()->create([
            'balance_kopeki' => 50000,
            'frozen_kopeki' => 10000,
            'total_earned_kopeki' => 100000,
            'total_withdrawn_kopeki' => 50000,
        ]);

        $summary = $this->financeService->getDriverFinancialSummary($wallet->driver_id);

        $this->assertEquals(500.0, $summary['balance_rubles']);
        $this->assertEquals(100.0, $summary['frozen_rubles']);
        $this->assertEquals(400.0, $summary['available_rubles']);
        $this->assertEquals(1000.0, $summary['total_earned_rubles']);
        $this->assertEquals(500.0, $summary['total_withdrawn_rubles']);
    }

    public function test_debit_driver_wallet_throws_on_insufficient_balance(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $wallet = TaxiDriverWallet::factory()->create([
            'balance_kopeki' => 1000,
        ]);

        $this->financeService->debitDriverWallet(
            driverId: $wallet->driver_id,
            amountKopeki: 5000,
            correlationId: 'test-correlation',
        );
    }
}
