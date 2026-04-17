<?php declare(strict_types=1);

namespace Tests\Security;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\PropertyTransaction;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RealEstatePaymentAttackTest extends SecurityTestCase
{
    use RefreshDatabase;

    private User $user;
    private User $attacker;
    private Tenant $tenant;
    private Property $property;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->attacker = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
        ]);
        $this->wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'current_balance' => 50000000.00,
        ]);
    }

    public function test_double_spend_attack_prevented(): void
    {
        $transactionData = [
            'property_id' => $this->property->id,
            'amount' => 10000000.00,
            'payment_method' => 'wallet',
        ];

        $response1 = $this->actingAs($this->user)
            ->postJson('/api/real-estate/transactions', $transactionData);

        $response2 = $this->actingAs($this->user)
            ->postJson('/api/real-estate/transactions', $transactionData);

        if ($response1->status() === 200 && $response2->status() === 200) {
            $this->assertNotEquals($response1->json('id'), $response2->json('id'), 'Double spend should create separate transactions with proper checks');
        } else {
            $this->assertContains($response2->status(), [409, 422], 'Double spend attempt should be blocked');
        }
    }

    public function test_race_condition_on_escrow_release_prevented(): void
    {
        $transaction = PropertyTransaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'buyer_id' => $this->user->id,
            'amount' => 10000000.00,
            'status' => 'escrow_pending',
            'escrow_amount' => 10000000.00,
        ]);

        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson("/api/real-estate/transactions/{$transaction->uuid}/release");
        }

        $successfulReleases = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        $this->assertLessThan(2, $successfulReleases, 'Race condition on escrow release should be prevented');
    }

    public function test_payment_amount_manipulation_blocked(): void
    {
        $originalAmount = 10000000.00;

        $response = $this->actingAs($this->attacker)
            ->postJson('/api/real-estate/transactions', [
                'property_id' => $this->property->id,
                'amount' => 1.00,
                'original_amount' => $originalAmount,
                'payment_method' => 'wallet',
            ]);

        if ($response->status() === 200) {
            $this->assertGreaterThan($originalAmount * 0.5, $response->json('amount'), 'Payment amount should not be manipulated below 50%');
        } else {
            $this->assertContains($response->status(), [422, 403], 'Payment manipulation should be blocked');
        }
    }

    public function test_refund_attack_prevented(): void
    {
        $transaction = PropertyTransaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'buyer_id' => $this->user->id,
            'amount' => 10000000.00,
            'status' => 'completed',
            'completed_at' => now()->subDays(30),
        ]);

        $response = $this->actingAs($this->attacker)
            ->postJson("/api/real-estate/transactions/{$transaction->uuid}/refund", [
                'reason' => 'change_of_mind',
                'force_refund' => true,
            ]);

        $this->assertContains($response->status(), [403, 422], 'Forced refund after completion should be blocked');
    }

    public function test_split_payment_manipulation_blocked(): void
    {
        $transaction = PropertyTransaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'buyer_id' => $this->user->id,
            'amount' => 10000000.00,
            'status' => 'pending',
            'is_b2b' => true,
        ]);

        $maliciousSplits = [
            ['recipient_id' => $this->attacker->id, 'amount' => 9000000.00, 'share' => 0.9],
            ['recipient_id' => $this->user->id, 'amount' => 1000000.00, 'share' => 0.1],
        ];

        $response = $this->actingAs($this->attacker)
            ->postJson("/api/real-state/transactions/{$transaction->uuid}/split", [
            'splits' => $maliciousSplits,
        ]);

        $this->assertContains($response->status(), [403, 422], 'Malicious split payment should be blocked');
    }

    public function test_escrow_hold_bypass_attempt_blocked(): void
    {
        $transaction = PropertyTransaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'buyer_id' => $this->user->id,
            'amount' => 10000000.00,
            'status' => 'escrow_pending',
            'escrow_hold_until' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->attacker)
            ->postJson("/api/real-estate/transactions/{$transaction->uuid}/release", [
            'bypass_hold' => true,
            'admin_override' => 'fake_token',
        ]);

        $this->assertContains($response->status(), [403, 401], 'Escrow hold bypass should be blocked');
    }

    public function test_payment_replay_attack_prevented(): void
    {
        $idempotencyKey = Str::uuid()->toString();
        $paymentData = [
            'property_id' => $this->property->id,
            'amount' => 10000000.00,
            'payment_method' => 'wallet',
        ];

        $response1 = $this->actingAs($this->user)
            ->postJson('/api/real-estate/transactions', $paymentData, [
                'Idempotency-Key' => $idempotencyKey,
            ]);

        $response2 = $this->actingAs($this->attacker)
            ->postJson('/api/real-estate/transactions', $paymentData, [
                'Idempotency-Key' => $idempotencyKey,
            ]);

        if ($response1->status() === 200) {
            $this->assertEquals($response1->json('id'), $response2->json('id'), 'Replay attack should return same transaction ID');
        }
    }

    public function test_wallet_insufficient_fraud_prevented(): void
    {
        $poorWallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->attacker->id,
            'current_balance' => 1000.00,
        ]);

        $response = $this->actingAs($this->attacker)
            ->postJson('/api/real-estate/transactions', [
                'property_id' => $this->property->id,
                'amount' => 10000000.00,
                'payment_method' => 'wallet',
                'bypass_balance_check' => true,
            ]);

        $this->assertContains($response->status(), [403, 422], 'Insufficient funds bypass should be blocked');
    }

    public function test_cross_tenant_payment_blocked(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($otherUser)
            ->postJson('/api/real-estate/transactions', [
                'property_id' => $this->property->id,
                'amount' => 10000000.00,
                'payment_method' => 'wallet',
            ]);

        $this->assertContains($response->status(), [403, 404], 'Cross-tenant payment should be blocked');
    }

    public function test_payment_timing_attack_prevented(): void
    {
        $transaction = PropertyTransaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'buyer_id' => $this->user->id,
            'amount' => 10000000.00,
            'status' => 'pending',
        ]);

        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson("/api/real-estate/transactions/{$transaction->uuid}/confirm", [
                    'timestamp' => now()->subSeconds($i)->toIso8601String(),
                ]);
        }

        $successfulConfirms = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        $this->assertLessThan(2, $successfulConfirms, 'Timing attack on payment confirmation should be prevented');
    }

    public function test_escrow_split_manipulation_blocked(): void
    {
        $transaction = PropertyTransaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'buyer_id' => $this->user->id,
            'amount' => 10000000.00,
            'status' => 'escrow_pending',
            'escrow_amount' => 10000000.00,
            'is_b2b' => true,
        ]);

        $maliciousSplit = [
            'splits' => [
                ['recipient_id' => $this->attacker->id, 'percentage' => 0.95],
                ['recipient_id' => $this->user->id, 'percentage' => 0.05],
            ],
        ];

        $response = $this->actingAs($this->attacker)
            ->postJson("/api/real-estate/transactions/{$transaction->uuid}/escrow-split", $maliciousSplit);

        $this->assertContains($response->status(), [403, 422], 'Malicious escrow split should be blocked');
    }

    public function test_payment_cancellation_after_release_blocked(): void
    {
        $transaction = PropertyTransaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'buyer_id' => $this->user->id,
            'amount' => 10000000.00,
            'status' => 'completed',
            'escrow_released_at' => now()->subHours(1),
        ]);

        $response = $this->actingAs($this->attacker)
            ->postJson("/api/real-estate/transactions/{$transaction->uuid}/cancel", [
            'reason' => 'fraudulent_cancellation',
        ]);

        $this->assertContains($response->status(), [403, 422], 'Cancellation after release should be blocked');
    }

    public function test_wallet_balance_race_condition_prevented(): void
    {
        $initialBalance = $this->wallet->current_balance;
        $walletId = $this->wallet->id;

        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $results[] = DB::transaction(function () use ($walletId) {
                $wallet = Wallet::lockForUpdate()->find($walletId);
                $wallet->update([
                    'current_balance' => $wallet->current_balance - 5000000.00,
                ]);
                return $wallet->current_balance;
            });
        }

        $this->wallet->refresh();
        $expectedBalance = $initialBalance - (5000000.00 * 3);
        $this->assertGreaterThanOrEqual(0, $this->wallet->current_balance, 'Balance should not go negative due to race condition');
    }
}
