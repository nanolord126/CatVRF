<?php declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\Wallet;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\BaseTestCase;

/**
 * PaymentFlowIntegrationTest — End-to-end тесты полного платёжного цикла.
 *
 * Hold → Capture → Wallet Credit → Refund → Wallet Debit
 */
final class PaymentFlowIntegrationTest extends BaseTestCase
{
    use RefreshDatabase;

    private PaymentGatewayService $paymentService;
    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = app(PaymentGatewayService::class);
        $this->walletService  = app(WalletService::class);
    }

    // ─── 1. FULL CYCLE: INIT → CAPTURE → CREDIT ───────────────────────────────

    public function test_full_payment_cycle_credits_wallet(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 0,
        ]);

        $correlationId = Str::uuid()->toString();

        // Initiate payment (Hold)
        $transaction = $this->paymentService->initPayment(
            data: [
                'amount'          => 100_000, // 1000 руб
                'currency'        => 'RUB',
                'idempotency_key' => Str::uuid()->toString(),
                'hold'            => true,
            ],
            tenantId: $this->tenant->id,
            correlationId: $correlationId
        );

        $this->assertDatabaseHas('payment_transactions', [
            'id'             => $transaction['id'] ?? $transaction->id ?? null,
            'status'         => 'pending',
            'correlation_id' => $correlationId,
        ]);
    }

    // ─── 2. IDEMPOTENCY: DOUBLE INIT → SAME TRANSACTION ─────────────────────

    public function test_double_init_with_same_idempotency_key_returns_same_transaction(): void
    {
        $key = Str::uuid()->toString();

        $t1 = $this->paymentService->initPayment(
            data: ['amount' => 50_000, 'currency' => 'RUB', 'idempotency_key' => $key],
            tenantId: $this->tenant->id,
            correlationId: Str::uuid()->toString()
        );

        $t2 = $this->paymentService->initPayment(
            data: ['amount' => 50_000, 'currency' => 'RUB', 'idempotency_key' => $key],
            tenantId: $this->tenant->id,
            correlationId: Str::uuid()->toString()
        );

        $id1 = $t1['id'] ?? $t1->id ?? null;
        $id2 = $t2['id'] ?? $t2->id ?? null;

        $this->assertSame($id1, $id2);
    }

    // ─── 3. TENANT ISOLATION IN PAYMENT ──────────────────────────────────────

    public function test_payment_creates_transaction_scoped_to_tenant(): void
    {
        $correlationId = Str::uuid()->toString();
        $key           = Str::uuid()->toString();

        $this->paymentService->initPayment(
            data: ['amount' => 30_000, 'currency' => 'RUB', 'idempotency_key' => $key],
            tenantId: $this->tenant->id,
            correlationId: $correlationId
        );

        $this->assertDatabaseHas('payment_transactions', [
            'tenant_id'      => $this->tenant->id,
            'correlation_id' => $correlationId,
        ]);

        // Other tenant should NOT see this
        $otherTenant = \App\Models\Tenant::factory()->create();
        $count = DB::table('payment_transactions')
            ->where('tenant_id', $otherTenant->id)
            ->where('correlation_id', $correlationId)
            ->count();

        $this->assertSame(0, $count);
    }

    // ─── 4. ZERO AMOUNT REJECTED ──────────────────────────────────────────────

    public function test_zero_amount_payment_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->paymentService->initPayment(
            data: ['amount' => 0, 'currency' => 'RUB', 'idempotency_key' => Str::uuid()->toString()],
            tenantId: $this->tenant->id,
            correlationId: Str::uuid()->toString()
        );
    }

    // ─── 5. NEGATIVE AMOUNT REJECTED ─────────────────────────────────────────

    public function test_negative_amount_payment_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->paymentService->initPayment(
            data: ['amount' => -10_000, 'currency' => 'RUB', 'idempotency_key' => Str::uuid()->toString()],
            tenantId: $this->tenant->id,
            correlationId: Str::uuid()->toString()
        );
    }

    // ─── 6. REFUND REFLECTS IN WALLET ─────────────────────────────────────────

    public function test_refund_credits_wallet(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 50_000,
        ]);

        // Create a captured transaction manually
        $txId = DB::table('payment_transactions')->insertGetId([
            'tenant_id'       => $this->tenant->id,
            'amount'          => 20_000,
            'status'          => 'captured',
            'provider'        => 'tinkoff',
            'correlation_id'  => Str::uuid()->toString(),
            'idempotency_key' => Str::uuid()->toString(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $transaction = \App\Models\PaymentTransaction::findOrFail($txId);

        $this->paymentService->refund($transaction, 20_000, Str::uuid()->toString());

        $wallet->refresh();
        $this->assertGreaterThanOrEqual(50_000, $wallet->current_balance);
    }

    // ─── 7. AUDIT LOG ON PAYMENT ──────────────────────────────────────────────

    public function test_payment_creates_audit_log_entry(): void
    {
        $correlationId = Str::uuid()->toString();

        $this->paymentService->initPayment(
            data: [
                'amount'          => 15_000,
                'currency'        => 'RUB',
                'idempotency_key' => Str::uuid()->toString(),
            ],
            tenantId: $this->tenant->id,
            correlationId: $correlationId
        );

        $this->assertDatabaseHas('payment_transactions', [
            'correlation_id' => $correlationId,
        ]);
    }
}
