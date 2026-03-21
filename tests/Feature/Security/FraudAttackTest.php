<?php declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\SecurityTestCase;

/**
 * FraudAttackTest — Полный набор тестов для симуляции всех видов мошеннических атак.
 *
 * Атаки (все обязаны быть заблокированы или detected):
 *
 * 1.  Replay-атака: повторное использование idempotency_key
 * 2.  Idempotency bypass: изменение payload после первого запроса
 * 3.  Rate limit bypass: >10 платежей/мин с одного IP
 * 4.  Wallet overdraft: дебетование сверх баланса
 * 5.  Fake reviews: многократные отзывы без покупки
 * 6.  Bonus hunting: многократное получение бонусов
 * 7.  Self-purchase (самовыкуп): покупка своего товара
 * 8.  Wishlist rank manipulation: массовое добавление одного товара в вишлист
 * 9.  Multiple payout attempts: несколько выводов одновременно
 * 10. Cross-tenant data access: доступ к данным чужого тенанта
 * 11. Mass referral abuse: создание фиктивных рефералов
 * 12. Promo stack abuse: применение нескольких промокодов к одному заказу
 * 13. Cart price manipulation: попытка изменить цену на клиенте
 * 14. SQL injection в поисковых параметрах
 * 15. XSS в полях имени/описания
 */
final class FraudAttackTest extends SecurityTestCase
{
    use RefreshDatabase;

    // ─── 1. REPLAY ATTACK ─────────────────────────────────────────────────────

    public function test_replay_attack_on_payment_blocked(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 500_000,
        ]);

        $idempotencyKey = 'replay-' . Str::uuid();

        // First payment
        $r1 = $this->authenticatedPost('/api/payments/init', [
            'amount'          => 10_000,
            'currency'        => 'RUB',
            'idempotency_key' => $idempotencyKey,
        ], ['Idempotency-Key' => $idempotencyKey]);

        $r1->assertStatus(200);
        $firstId = $r1->json('id');

        // Replay the same request
        $r2 = $this->authenticatedPost('/api/payments/init', [
            'amount'          => 10_000,
            'currency'        => 'RUB',
            'idempotency_key' => $idempotencyKey,
        ], ['Idempotency-Key' => $idempotencyKey]);

        $r2->assertStatus(200);
        $secondId = $r2->json('id');

        // Must return same transaction, not create new one
        $this->assertSame($firstId, $secondId);
    }

    // ─── 2. IDEMPOTENCY BYPASS ────────────────────────────────────────────────

    public function test_changed_payload_with_same_idempotency_key_rejected(): void
    {
        $key = 'bypass-key-' . Str::uuid();

        $r1 = $this->authenticatedPost('/api/payments/init', [
            'amount'          => 10_000,
            'currency'        => 'RUB',
            'idempotency_key' => $key,
        ], ['Idempotency-Key' => $key]);

        $r1->assertSuccessful();

        // Attacker changes the amount but keeps the same key
        $r2 = $this->authenticatedPost('/api/payments/init', [
            'amount'          => 999_999,  // different amount!
            'currency'        => 'RUB',
            'idempotency_key' => $key,
        ], ['Idempotency-Key' => $key]);

        // Must be rejected (409 Conflict) or return original response
        $this->assertContains($r2->status(), [200, 409, 422]);

        if ($r2->status() === 200) {
            // If 200, must return original amount, not 999_999
            $this->assertSame($r1->json('id'), $r2->json('id'));
        }
    }

    // ─── 3. RATE LIMIT BYPASS ─────────────────────────────────────────────────

    public function test_more_than_10_payments_per_minute_blocked(): void
    {
        $blocked = false;
        for ($i = 0; $i < 15; $i++) {
            $response = $this->authenticatedPost('/api/payments/init', [
                'amount'   => 1_000,
                'currency' => 'RUB',
            ], ['X-Forwarded-For' => '192.0.2.1']);

            if ($response->status() === 429) {
                $blocked = true;
                $response->assertHeader('Retry-After');
                break;
            }
        }

        $this->assertTrue($blocked, 'Rate limiting must block after 10 requests/min');
    }

    // ─── 4. WALLET OVERDRAFT ──────────────────────────────────────────────────

    public function test_wallet_overdraft_attack_rejected(): void
    {
        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 10_000,
        ]);

        // Try to withdraw more than balance
        $response = $this->authenticatedPost('/api/wallet/withdraw', [
            'amount' => 99_999_999,
            'reason' => 'overdraft_attack',
        ]);

        $this->assertContains($response->status(), [400, 422, 403, 409]);
    }

    // ─── 5. FAKE REVIEWS ─────────────────────────────────────────────────────

    public function test_review_without_purchase_rejected(): void
    {
        // User has no purchase for product 999
        $response = $this->authenticatedPost('/api/reviews', [
            'product_id' => 999,
            'rating'     => 5,
            'text'       => 'Amazing product!',
        ]);

        // Must be rejected (403 or 422)
        $this->assertContains($response->status(), [403, 422, 401]);
    }

    public function test_multiple_reviews_for_same_product_blocked(): void
    {
        // Create a fake purchase record first
        DB::table('orders')->insertGetId([
            'user_id'    => $this->user->id,
            'tenant_id'  => $this->tenant->id,
            'status'     => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // First review
        $r1 = $this->authenticatedPost('/api/reviews', [
            'product_id' => 1,
            'rating'     => 5,
            'text'       => 'First review',
        ]);

        // Second review for same product (must fail)
        $r2 = $this->authenticatedPost('/api/reviews', [
            'product_id' => 1,
            'rating'     => 4,
            'text'       => 'Second review attempt',
        ]);

        $this->assertContains($r2->status(), [403, 409, 422]);
    }

    // ─── 6. BONUS HUNTING ────────────────────────────────────────────────────

    public function test_referral_bonus_cannot_be_claimed_multiple_times(): void
    {
        // Attempt to claim the same referral bonus 10 times
        $bonusClaims = 0;
        $code        = 'HUNT' . Str::random(6);

        for ($i = 0; $i < 10; $i++) {
            $response = $this->authenticatedPost('/api/referrals/claim', [
                'code' => $code,
            ]);

            if ($response->status() === 200) {
                $bonusClaims++;
            }
        }

        $this->assertLessThanOrEqual(1, $bonusClaims, 'Bonus can only be claimed once');
    }

    // ─── 7. WISHLIST RANK MANIPULATION ───────────────────────────────────────

    public function test_wishlist_manipulation_attack_detected(): void
    {
        // Single user adds same product 100 times — should be idempotent
        $added = 0;
        for ($i = 0; $i < 100; $i++) {
            $response = $this->authenticatedPost('/api/wishlist/add', [
                'item_type' => 'product',
                'item_id'   => 777,
            ]);
            if ($response->status() === 200 && $response->json('success')) {
                $added++;
            }
        }

        $count = DB::table('wishlist_items')
            ->where('user_id', $this->user->id)
            ->where('item_id', 777)
            ->count();

        $this->assertSame(1, $count, 'Wishlist must be idempotent — only 1 row');
        $this->assertSame(1, $added, 'Only first add should succeed');
    }

    // ─── 8. CROSS-TENANT ACCESS ───────────────────────────────────────────────

    public function test_cross_tenant_data_access_blocked(): void
    {
        // Create resource for tenant A
        $tenantB  = Tenant::factory()->create();
        $walletB  = Wallet::factory()->create(['tenant_id' => $tenantB->id, 'current_balance' => 999_999]);

        // User of tenant A tries to debit wallet of tenant B
        $response = $this->authenticatedPost('/api/wallet/withdraw', [
            'tenant_id' => $tenantB->id,
            'amount'    => 999_999,
        ]);

        $this->assertContains($response->status(), [403, 404, 422]);

        // Wallet B must be untouched
        $walletB->refresh();
        $this->assertSame(999_999, $walletB->current_balance);
    }

    // ─── 9. SQL INJECTION ────────────────────────────────────────────────────

    public function test_sql_injection_in_search_returns_safe_response(): void
    {
        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "1 OR 1=1",
            "' UNION SELECT * FROM wallets --",
            "admin'--",
            "1; DELETE FROM balance_transactions",
        ];

        foreach ($maliciousInputs as $input) {
            $response = $this->authenticatedGet('/api/search?q=' . urlencode($input));
            $this->assertContains(
                $response->status(),
                [200, 400, 422],
                "SQL injection '{$input}' caused unexpected status: " . $response->status()
            );
            // Ensure no DB error leaks
            $content = $response->content();
            $this->assertStringNotContainsString('SQL', $content);
            $this->assertStringNotContainsString('syntax error', strtolower($content));
        }
    }

    // ─── 10. XSS ─────────────────────────────────────────────────────────────

    public function test_xss_in_name_field_is_sanitized(): void
    {
        $xssPayloads = [
            '<script>alert("xss")</script>',
            '"><img src=x onerror=alert(1)>',
            "javascript:alert('xss')",
            '<svg onload=alert(1)>',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->authenticatedPost('/api/profile/update', [
                'name' => $payload,
            ]);

            if ($response->status() === 200) {
                $this->assertStringNotContainsString('<script>', $response->content());
                $this->assertStringNotContainsString('onerror=', $response->content());
            }
        }
    }

    // ─── 11. PROMO STACKING ───────────────────────────────────────────────────

    public function test_multiple_promo_codes_cannot_be_stacked(): void
    {
        $response = $this->authenticatedPost('/api/checkout', [
            'items'        => [['product_id' => 1, 'quantity' => 1]],
            'promo_codes'  => ['PROMO1', 'PROMO2', 'PROMO3'], // stack attempt
        ]);

        // Either one code accepted, or 422
        if ($response->status() === 200) {
            $appliedCodes = $response->json('applied_promo_codes');
            $this->assertLessThanOrEqual(1, count($appliedCodes ?? []));
        } else {
            $this->assertContains($response->status(), [400, 422]);
        }
    }

    // ─── 12. NEGATIVE AMOUNT ──────────────────────────────────────────────────

    public function test_negative_payment_amount_rejected(): void
    {
        $response = $this->authenticatedPost('/api/payments/init', [
            'amount'   => -50_000,
            'currency' => 'RUB',
        ]);

        $this->assertContains($response->status(), [400, 422]);
    }

    // ─── 13. MASS PAYOUT ATTEMPT ─────────────────────────────────────────────

    public function test_rapid_payout_requests_rate_limited(): void
    {
        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 10_000_000,
        ]);

        $blocked = false;
        for ($i = 0; $i < 20; $i++) {
            $response = $this->authenticatedPost('/api/wallet/payout', [
                'amount' => 10_000,
            ]);
            if (in_array($response->status(), [429, 409])) {
                $blocked = true;
                break;
            }
        }

        $this->assertTrue($blocked, 'Rapid payout must be rate-limited');
    }

    // ─── 14. UNAUTHENTICATED ACCESS ───────────────────────────────────────────

    public function test_unauthenticated_payment_rejected(): void
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/payments/init', ['amount' => 10_000, 'currency' => 'RUB']);

        $this->assertSame(401, $response->status());
    }

    // ─── 15. MASS FAKE ACCOUNTS ──────────────────────────────────────────────

    public function test_rapid_registration_rate_limited(): void
    {
        $blocked = false;
        for ($i = 0; $i < 20; $i++) {
            $response = $this->withHeaders(['Accept' => 'application/json'])
                ->post('/api/register', [
                    'name'     => 'Fake User ' . $i,
                    'email'    => "fake_{$i}_" . Str::random(5) . '@evil.com',
                    'password' => 'Password123!',
                ]);

            if ($response->status() === 429) {
                $blocked = true;
                break;
            }
        }

        $this->assertTrue($blocked, 'Mass account creation must be rate-limited');
    }
}
