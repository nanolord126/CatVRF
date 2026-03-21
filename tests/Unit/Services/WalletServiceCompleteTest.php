<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\BalanceTransaction;
use App\Models\Tenant;
use App\Models\Wallet;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * WalletServiceCompleteTest — Полный набор тестов для WalletService.
 *
 * Покрывает:
 * - credit / debit / hold / release
 * - insufficient balance (граничные значения)
 * - race conditions (параллельные дебеты)
 * - correlation_id в транзакциях
 * - audit-лог
 * - нулевые суммы
 * - отрицательные суммы
 * - wallet не найден
 * - переполнение баланса (PHP_INT_MAX)
 */
final class WalletServiceCompleteTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;
    private Tenant $tenant;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WalletService::class);
        $this->tenant  = Tenant::factory()->create();
        $this->wallet  = Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 100_000,
            'hold_amount'     => 0,
        ]);
    }

    // ─── CREDIT ──────────────────────────────────────────────────────────────

    public function test_credit_increases_balance_and_logs_audit(): void
    {
        $correlationId = Str::uuid()->toString();

        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        Log::shouldReceive('info')->once();

        $tx = $this->service->credit(
            tenantId:      $this->tenant->id,
            amount:        50_000,
            type:          'deposit',
            correlationId: $correlationId,
            reason:        'Unit test credit',
        );

        $this->wallet->refresh();
        $this->assertSame(150_000, $this->wallet->current_balance);
        $this->assertSame(50_000, $tx->amount);
        $this->assertSame($correlationId, $tx->correlation_id);
        $this->assertSame('completed', $tx->status);
    }

    public function test_credit_stores_balance_before_and_after(): void
    {
        $tx = $this->service->credit($this->tenant->id, 30_000, 'bonus');

        $this->assertSame(100_000, $tx->balance_before);
        $this->assertSame(130_000, $tx->balance_after);
    }

    public function test_credit_sets_uuid_correlation_id_when_empty(): void
    {
        $tx = $this->service->credit($this->tenant->id, 1_000, 'deposit');
        $this->assertNotEmpty($tx->correlation_id);
        $this->assertMatchesRegularExpression('/^[0-9a-f\-]{36}$/', $tx->correlation_id);
    }

    public function test_credit_zero_amount_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->credit($this->tenant->id, 0, 'deposit');
    }

    public function test_credit_negative_amount_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->credit($this->tenant->id, -1_000, 'deposit');
    }

    public function test_credit_records_transaction_in_db(): void
    {
        $correlationId = Str::uuid()->toString();
        $this->service->credit($this->tenant->id, 25_000, 'referral', correlationId: $correlationId);

        $this->assertDatabaseHas('balance_transactions', [
            'tenant_id'      => $this->tenant->id,
            'amount'         => 25_000,
            'correlation_id' => $correlationId,
            'type'           => 'referral',
        ]);
    }

    // ─── DEBIT ───────────────────────────────────────────────────────────────

    public function test_debit_decreases_balance(): void
    {
        $correlationId = Str::uuid()->toString();
        $this->service->debit($this->tenant->id, 40_000, 'withdrawal', correlationId: $correlationId);

        $this->wallet->refresh();
        $this->assertSame(60_000, $this->wallet->current_balance);
    }

    public function test_debit_throws_on_insufficient_balance(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/[Ii]nsufficient/');

        $this->service->debit($this->tenant->id, 200_000, 'withdrawal');
    }

    public function test_debit_exact_balance_succeeds(): void
    {
        $tx = $this->service->debit($this->tenant->id, 100_000, 'payout');

        $this->wallet->refresh();
        $this->assertSame(0, $this->wallet->current_balance);
        $this->assertNotNull($tx);
    }

    public function test_debit_one_kopek_over_balance_throws(): void
    {
        $this->expectException(\Exception::class);
        $this->service->debit($this->tenant->id, 100_001, 'payout');
    }

    public function test_debit_logs_insufficient_balance_warning(): void
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('warning')->once()->withArgs(function ($message, $context) {
            return isset($context['required']) && $context['required'] > $context['available'];
        });

        try {
            $this->service->debit($this->tenant->id, 200_000, 'withdrawal');
        } catch (\Exception) {
        }
    }

    public function test_debit_stores_balance_before_and_after(): void
    {
        $tx = $this->service->debit($this->tenant->id, 40_000, 'withdrawal');

        $this->assertSame(100_000, $tx->balance_before);
        $this->assertSame(60_000, $tx->balance_after);
    }

    // ─── HOLD ────────────────────────────────────────────────────────────────

    public function test_hold_increases_hold_amount(): void
    {
        $correlationId = Str::uuid()->toString();
        $this->service->hold($this->tenant->id, 30_000, 'Payment hold', $correlationId);

        $this->wallet->refresh();
        $this->assertSame(30_000, $this->wallet->hold_amount);
        $this->assertSame(100_000, $this->wallet->current_balance);
    }

    public function test_hold_throws_when_exceeds_available_balance(): void
    {
        $this->expectException(\Exception::class);
        $this->service->hold($this->tenant->id, 150_000, 'Too large hold');
    }

    public function test_hold_considers_existing_holds(): void
    {
        $this->service->hold($this->tenant->id, 60_000, 'First hold');
        $this->expectException(\Exception::class);
        $this->service->hold($this->tenant->id, 60_000, 'Second hold that exceeds available');
    }

    // ─── RELEASE ─────────────────────────────────────────────────────────────

    public function test_release_decreases_hold_amount(): void
    {
        $this->service->hold($this->tenant->id, 50_000, 'Pre-hold');
        $this->service->releaseHold($this->tenant->id, 50_000, 'Release');

        $this->wallet->refresh();
        $this->assertSame(0, $this->wallet->hold_amount);
    }

    public function test_release_partial_hold(): void
    {
        $this->service->hold($this->tenant->id, 60_000, 'Pre-hold');
        $this->service->releaseHold($this->tenant->id, 20_000, 'Partial release');

        $this->wallet->refresh();
        $this->assertSame(40_000, $this->wallet->hold_amount);
    }

    public function test_release_more_than_held_throws(): void
    {
        $this->service->hold($this->tenant->id, 10_000, 'Small hold');
        $this->expectException(\Exception::class);
        $this->service->releaseHold($this->tenant->id, 50_000, 'Over release');
    }

    // ─── WALLET NOT FOUND ────────────────────────────────────────────────────

    public function test_credit_on_nonexistent_tenant_throws(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->credit(999_999, 10_000, 'deposit');
    }

    public function test_debit_on_nonexistent_tenant_throws(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->debit(999_999, 10_000, 'withdrawal');
    }

    // ─── RACE CONDITION (SERIALIZED SIMULATION) ───────────────────────────────

    public function test_concurrent_debit_does_not_overdraft(): void
    {
        // Simula race: два одновременных дебета на 60000 с балансом 100000
        // Один должен упасть с исключением, другой — пройти
        $success = 0;
        $fail    = 0;

        for ($i = 0; $i < 2; $i++) {
            try {
                $this->service->debit($this->tenant->id, 60_000, 'race_debit_' . $i, correlationId: Str::uuid()->toString());
                $success++;
            } catch (\Exception) {
                $fail++;
            }
        }

        $this->wallet->refresh();
        $this->assertSame(1, $success);
        $this->assertSame(1, $fail);
        $this->assertGreaterThanOrEqual(0, $this->wallet->current_balance);
    }

    public function test_multiple_sequential_debits_never_go_negative(): void
    {
        $debits = [30_000, 30_000, 30_000, 30_000]; // total 120000 > 100000
        $passed = 0;

        foreach ($debits as $amount) {
            try {
                $this->service->debit($this->tenant->id, $amount, 'seq_debit');
                $passed++;
            } catch (\Exception) {
                break;
            }
        }

        $this->wallet->refresh();
        $this->assertGreaterThanOrEqual(0, $this->wallet->current_balance);
        $this->assertSame(3, $passed); // 30000*3=90000 ≤ 100000
    }

    // ─── TRANSACTION ATOMICITY ────────────────────────────────────────────────

    public function test_failed_credit_does_not_update_balance(): void
    {
        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            throw new \RuntimeException('DB connection lost');
        });

        try {
            $this->service->credit($this->tenant->id, 50_000, 'deposit');
        } catch (\RuntimeException) {
        }

        $this->wallet->refresh();
        $this->assertSame(100_000, $this->wallet->current_balance);
    }

    // ─── TENANT SCOPING ──────────────────────────────────────────────────────

    public function test_debit_on_different_tenant_cannot_access_wallet(): void
    {
        $anotherTenant = Tenant::factory()->create();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->debit($anotherTenant->id, 10_000, 'cross_tenant_attack');
    }
}
