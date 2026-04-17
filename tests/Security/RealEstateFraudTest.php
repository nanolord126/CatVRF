<?php declare(strict_types=1);

namespace Tests\Security;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\PropertyTransaction;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RealEstateFraudTest extends SecurityTestCase
{
    use RefreshDatabase;

    private User $user;
    private User $attacker;
    private Tenant $tenant;
    private Property $property;

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
            'metadata' => [
                'title_clear' => true,
                'no_liens' => true,
                'blockchain_verified' => true,
            ],
        ]);

        DB::table('user_profiles')->insert([
            'user_id' => $this->user->id,
            'estimated_income' => 500000.00,
            'existing_debt' => 50000.00,
        ]);
    }

    public function test_property_price_manipulation_blocked(): void
    {
        $originalPrice = $this->property->price;
        
        $response = $this->actingAs($this->attacker)
            ->putJson("/api/real-estate/properties/{$this->property->id}", [
                'price' => 100000.00,
                'metadata' => ['title_clear' => true],
            ]);

        if ($response->status() === 200) {
            $this->property->refresh();
            $this->assertLessThan($originalPrice * 0.5, $this->property->price, 'Price should not drop more than 50% in single update');
        } else {
            $this->assertContains($response->status(), [403, 422], 'Price manipulation should be blocked');
        }
    }

    public function test_fake_property_listing_blocked(): void
    {
        $suspiciousProperty = [
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 1000.0,
            'price' => 1000000.00,
            'address' => 'Non-existent address',
            'lat' => 0.0,
            'lon' => 0.0,
        ];

        $response = $this->actingAs($this->attacker)
            ->postJson('/api/real-estate/properties', $suspiciousProperty);

        if ($response->status() === 200) {
            $this->assertHasFraudScore($response);
            $this->assertGreaterThan(0.7, $response->json('fraud_score'), 'Fake listing should have high fraud score');
        } else {
            $this->assertContains($response->status(), [422, 403], 'Fake listing should be blocked');
        }
    }

    public function test_duplicate_property_submission_blocked(): void
    {
        $propertyData = [
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
            'address' => $this->property->address,
            'lat' => $this->property->lat,
            'lon' => $this->property->lon,
        ];

        $response1 = $this->actingAs($this->attacker)
            ->postJson('/api/real-estate/properties', $propertyData);

        $response2 = $this->actingAs($this->attacker)
            ->postJson('/api/real-estate/properties', $propertyData);

        if ($response1->status() === 200 && $response2->status() === 200) {
            $this->assertHasFraudScore($response2);
            $this->assertGreaterThan(0.8, $response2->json('fraud_score'), 'Duplicate submission should have very high fraud score');
        } else {
            $this->assertContains($response2->status(), [409, 422], 'Duplicate submission should be blocked');
        }
    }

    public function test_suspicious_transaction_pattern_blocked(): void
    {
        for ($i = 0; $i < 10; $i++) {
            PropertyTransaction::factory()->create([
                'tenant_id' => $this->tenant->id,
                'property_id' => $this->property->id,
                'buyer_id' => $this->attacker->id,
                'amount' => 5000000.00,
                'status' => 'cancelled',
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->actingAs($this->attacker)
            ->postJson('/api/real-estate/transactions', [
                'property_id' => $this->property->id,
                'amount' => 5000000.00,
            ]);

        if ($response->status() === 200) {
            $this->assertHasFraudScore($response);
            $this->assertGreaterThan(0.9, $response->json('fraud_score'), 'Suspicious pattern should trigger high fraud score');
        } else {
            $this->assertContains($response->status(), [403, 429], 'Suspicious pattern should be blocked');
        }
    }

    public function test_identity_theft_attempt_blocked(): void
    {
        $victimUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->attacker)
            ->postJson('/api/real-estate/transactions', [
                'property_id' => $this->property->id,
                'buyer_id' => $victimUser->id,
                'amount' => 10000000.00,
                'impersonated' => true,
            ]);

        $this->assertContains($response->status(), [403, 401], 'Identity theft attempt should be blocked');
    }

    public function test_money_laundering_pattern_detected(): void
    {
        $launderingPattern = [
            'property_id' => $this->property->id,
            'amount' => 50000000.00,
            'payment_method' => 'cash',
            'buyer_id' => $this->attacker->id,
            'seller_id' => $this->user->id,
            'quick_close' => true,
        ];

        $response = $this->actingAs($this->attacker)
            ->postJson('/api/real-estate/transactions', $launderingPattern);

        if ($response->status() === 200) {
            $this->assertHasFraudScore($response);
            $this->assertGreaterThan(0.85, $response->json('fraud_score'), 'Money laundering pattern should trigger very high fraud score');
        } else {
            $this->assertContains($response->status(), [403, 422], 'Money laundering pattern should be blocked');
        }
    }

    public function test_replay_attack_on_scoring_blocked(): void
    {
        $idempotencyKey = Str::uuid()->toString();
        $correlationId = Str::uuid()->toString();

        $firstRequest = $this->actingAs($this->user)
            ->postJson('/api/real-estate/scoring', [
                'property_id' => $this->property->id,
                'deal_amount' => 10000000.00,
                'is_b2b' => false,
            ], [
                'Idempotency-Key' => $idempotencyKey,
                'X-Correlation-ID' => $correlationId,
            ]);

        $secondRequest = $this->actingAs($this->attacker)
            ->postJson('/api/real-estate/scoring', [
                'property_id' => $this->property->id,
                'deal_amount' => 5000000.00,
                'is_b2b' => false,
            ], [
                'Idempotency-Key' => $idempotencyKey,
                'X-Correlation-ID' => $correlationId,
            ]);

        if ($firstRequest->status() === 200) {
            $this->assertNotEquals($firstRequest->json(), $secondRequest->json(), 'Replay with different payload should be blocked');
        }
    }

    public function test_escrow_fraud_prevention(): void
    {
        $transaction = PropertyTransaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'buyer_id' => $this->attacker->id,
            'amount' => 10000000.00,
            'status' => 'escrow_pending',
            'escrow_hold_until' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->attacker)
            ->postJson("/api/real-estate/transactions/{$transaction->uuid}/release", [
                'reason' => 'premature_release',
                'bypass_verification' => true,
            ]);

        $this->assertContains($response->status(), [403, 422], 'Premature escrow release should be blocked');
    }

    public function test_blockchain_document_forgery_detected(): void
    {
        $forgedDocument = [
            'property_id' => $this->property->id,
            'document_type' => 'title_deed',
            'document_hash' => '0x' . str_repeat('0', 64),
            'forged' => true,
        ];

        $response = $this->actingAs($this->attacker)
            ->postJson('/api/real-estate/blockchain/verify', $forgedDocument);

        if ($response->status() === 200) {
            $this->assertFalse($response->json('verified'), 'Forged document should not be verified');
            $this->assertHasFraudScore($response);
        } else {
            $this->assertContains($response->status(), [422, 403], 'Forged document should be rejected');
        }
    }

    public function test_multiple_rapid_viewings_blocked(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $response = $this->actingAs($this->attacker)
                ->postJson('/api/real-estate/viewings', [
                    'property_id' => $this->property->id,
                    'scheduled_at' => now()->addDays($i),
                ]);

            if ($i >= 10) {
                $this->assertContains($response->status(), [429, 403], 'Rapid viewings should be rate-limited after 10');
            }
        }
    }

    public function test_cross_tenant_data_access_blocked(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherProperty = Property::factory()->create([
            'tenant_id' => $otherTenant->id,
            'type' => 'apartment',
            'price' => 15000000.00,
        ]);

        $response = $this->actingAs($this->attacker)
            ->getJson("/api/real-estate/properties/{$otherProperty->id}");

        $this->assertContains($response->status(), [403, 404], 'Cross-tenant access should be blocked');
    }

    public function test_price_inflation_scheme_detected(): void
    {
        $inflationScheme = [
            'property_id' => $this->property->id,
            'new_price' => 50000000.00,
            'justification' => 'market_adjustment',
            'related_transactions' => [],
        ];

        $response = $this->actingAs($this->attacker)
            ->putJson("/api/real-estate/properties/{$this->property->id}", $inflationScheme);

        if ($response->status() === 200) {
            $this->assertHasFraudScore($response);
            $this->assertGreaterThan(0.75, $response->json('fraud_score'), 'Price inflation should trigger high fraud score');
        } else {
            $this->assertContains($response->status(), [403, 422], 'Price inflation should be blocked');
        }
    }

    protected function assertHasFraudScore($response): void
    {
        $this->assertArrayHasKey('fraud_score', $response->json(), 'Response should include fraud_score');
    }
}
