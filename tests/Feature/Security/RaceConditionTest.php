<?php declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * RaceConditionTest — Тесты гонки потоков (race conditions).
 *
 * Симулирует параллельные запросы в одном потоке:
 * - Двойное списание с кошелька (overdraft attack)
 * - Двойное создание брони (double-booking)
 * - Параллельный резерв склада
 * - Параллельный вывод средств
 * - Параллельный кредит (concurrency в credit)
 * - Параллельная выдача бонусов
 *
 * ВНИМАНИЕ: В PHP тесты выполняются серийно, поэтому race conditions
 * симулируются через последовательные операции с обходом кэша.
 */
final class RaceConditionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user   = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->wallet = Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 100_000,
            'hold_amount'     => 0,
        ]);
        app()->bind('tenant', fn () => $this->tenant);
    }

    // ─── WALLET OVERDRAFT ─────────────────────────────────────────────────────

    /**
     * Симуляция: два потока пытаются снять 60 000 с баланса 100 000.
     * Только один должен успешно выполниться.
     */
    public function test_wallet_cannot_go_negative_under_rapid_debits(): void
    {
        $walletService = app(\App\Services\Wallet\WalletService::class);
        $errors        = 0;
        $successes     = 0;

        // 5 попыток вывести по 30 000 (итого 150 000 > 100 000)
        for ($i = 0; $i < 5; $i++) {
            try {
                $walletService->debit(
                    $this->tenant->id,
                    30_000,
                    'payout',
                    correlationId: Str::uuid()->toString()
                );
                $successes++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        $this->wallet->refresh();
        $this->assertGreaterThanOrEqual(0, $this->wallet->current_balance);
        $this->assertSame(3, $successes); // 3×30=90 ≤ 100
        $this->assertSame(2, $errors);
    }

    /**
     * Hold + debit race: hold резервирует средства,
     * параллельный debit не должен обойти hold.
     */
    public function test_hold_prevents_overdraft_in_race(): void
    {
        $walletService = app(\App\Services\Wallet\WalletService::class);

        // Hold 80 000 из 100 000
        $walletService->hold($this->tenant->id, 80_000, 'Pending payment');

        // Попытка debit на 40 000 — должна провалиться (доступно только 20 000)
        $this->expectException(\Exception::class);
        $walletService->debit($this->tenant->id, 40_000, 'race_debit');
    }

    /**
     * Массовый credit: 1000 кредитов по 100 копеек = 100 000 копеек итого.
     * Итоговый баланс должен быть точно начальный + 100 000.
     */
    public function test_mass_credits_sum_correctly(): void
    {
        $walletService = app(\App\Services\Wallet\WalletService::class);
        $initial       = $this->wallet->current_balance;

        for ($i = 0; $i < 100; $i++) {
            $walletService->credit($this->tenant->id, 1_000, 'deposit', correlationId: Str::uuid()->toString());
        }

        $this->wallet->refresh();
        $this->assertSame($initial + 100_000, $this->wallet->current_balance);
    }

    // ─── INVENTORY RACE ───────────────────────────────────────────────────────

    public function test_inventory_reserve_cannot_exceed_stock(): void
    {
        $inventoryService = app(\App\Services\Inventory\InventoryManagementService::class);
        $item             = \App\Models\InventoryItem::factory()->create([
            'tenant_id'     => $this->tenant->id,
            'current_stock' => 10,
            'hold_stock'    => 0,
        ]);

        $success = 0;
        for ($i = 0; $i < 20; $i++) {
            if ($inventoryService->reserveStock($item->id, 1, 'order', $i)) {
                $success++;
            }
        }

        $item->refresh();
        $this->assertSame(10, $success);
        $this->assertSame(10, $item->hold_stock);
        $this->assertSame(0, $item->current_stock - $item->hold_stock);
    }

    // ─── PAYMENT IDEMPOTENCY RACE ────────────────────────────────────────────

    public function test_same_idempotency_key_prevents_double_payment(): void
    {
        $idempotencyKey = 'race-idem-' . Str::uuid();
        $paymentService = app(\App\Services\Payment\PaymentGatewayService::class);

        // Первый платёж
        $tx1 = $paymentService->initPayment([
            'amount'          => 50_000,
            'currency'        => 'RUB',
            'idempotency_key' => $idempotencyKey,
        ], $this->tenant->id, Str::uuid()->toString());

        // Второй платёж с тем же ключом
        $tx2 = $paymentService->initPayment([
            'amount'          => 50_000,
            'currency'        => 'RUB',
            'idempotency_key' => $idempotencyKey,
        ], $this->tenant->id, Str::uuid()->toString());

        // Должны быть одним и тем же объектом или ID должны совпадать
        $count = PaymentTransaction::where('idempotency_key', $idempotencyKey)->count();
        $this->assertSame(1, $count);
    }

    // ─── PROMO RACE ───────────────────────────────────────────────────────────

    public function test_promo_budget_cannot_exceed_limit_under_rapid_use(): void
    {
        // Создаём промо с бюджетом 10 000 копеек, скидка 1000 каждый раз
        $promoCampaign = DB::table('promo_campaigns')->insertGetId([
            'tenant_id'       => $this->tenant->id,
            'name'            => 'Race Promo',
            'type'            => 'fixed_amount',
            'code'            => 'RACE' . Str::random(4),
            'budget'          => 10_000,
            'spent_budget'    => 0,
            'max_uses_total'  => 100,
            'status'          => 'active',
            'start_at'        => now()->subDay(),
            'end_at'          => now()->addDay(),
            'correlation_id'  => Str::uuid()->toString(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // 20 попыток применить промо на 1000 каждая = 20 000 > 10 000
        $applied = 0;
        for ($i = 0; $i < 20; $i++) {
            try {
                DB::transaction(function () use ($promoCampaign, $i, &$applied) {
                    $campaign = DB::table('promo_campaigns')
                        ->where('id', $promoCampaign)
                        ->lockForUpdate()
                        ->first();

                    if ($campaign->spent_budget + 1_000 > $campaign->budget) {
                        throw new \Exception('Budget exhausted');
                    }

                    DB::table('promo_campaigns')
                        ->where('id', $promoCampaign)
                        ->increment('spent_budget', 1_000);

                    $applied++;
                });
            } catch (\Exception) {
            }
        }

        $campaign = DB::table('promo_campaigns')->find($promoCampaign);
        $this->assertSame(10, $applied);
        $this->assertSame(10_000, $campaign->spent_budget);
        $this->assertLessThanOrEqual(10_000, $campaign->spent_budget);
    }

    // ─── REFERRAL DOUBLE-CLAIM ────────────────────────────────────────────────

    public function test_referral_bonus_cannot_be_claimed_twice(): void
    {
        $referralId = DB::table('referrals')->insertGetId([
            'referrer_id'       => $this->user->id,
            'referral_code'     => 'REF' . Str::random(6),
            'status'            => 'qualified',
            'bonus_amount'      => 100_000,
            'correlation_id'    => Str::uuid()->toString(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $claimed = 0;
        for ($i = 0; $i < 5; $i++) {
            try {
                DB::transaction(function () use ($referralId, &$claimed) {
                    $referral = DB::table('referrals')
                        ->where('id', $referralId)
                        ->lockForUpdate()
                        ->first();

                    if ($referral->status === 'rewarded') {
                        throw new \Exception('Already rewarded');
                    }

                    DB::table('referrals')->where('id', $referralId)->update(['status' => 'rewarded']);
                    $claimed++;
                });
            } catch (\Exception) {
            }
        }

        $this->assertSame(1, $claimed);
    }
}
