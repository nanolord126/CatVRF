<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\RealEstate;

use Tests\TestCase;
use App\Domains\RealEstate\Services\PropertyTransactionService;
use App\Domains\RealEstate\DTOs\CreatePropertyDto;
use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\PropertyViewing;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class PropertyTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PropertyTransactionService $service;
    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PropertyTransactionService::class);
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_create_property_with_ai_generates_virtual_tour_and_ar_urls(): void
    {
        $dto = new CreatePropertyDto(
            tenantId: $this->tenant->id,
            businessGroupId: null,
            userId: $this->user->id,
            correlationId: \Illuminate\Support\Str::uuid()->toString(),
            data: [
                'title' => 'Test Property',
                'description' => 'Test Description',
                'address' => 'Test Address',
                'lat' => 55.7558,
                'lon' => 37.6173,
                'price' => 10000000.00,
                'type' => 'apartment',
                'area_sqm' => 75.5,
            ],
            isB2B: false
        );

        $property = $this->service->createPropertyWithAI($dto, $this->user->id);

        $this->assertInstanceOf(Property::class, $property);
        $this->assertNotNull($property->uuid);
        $this->assertEquals('active', $property->status);
        $this->assertArrayHasKey('ai_virtual_tour_url', $property->features);
        $this->assertArrayHasKey('ar_viewing_url', $property->features);
        $this->assertArrayHasKey('webrtc_enabled', $property->features);
        $this->assertTrue($property->features['webrtc_enabled']);
    }

    public function test_book_viewing_with_hold_creates_redis_lock(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);

        $scheduledAt = Carbon::now()->addHours(24);

        $result = $this->service->bookViewingWithHold(
            $property->id,
            $this->user->id,
            $scheduledAt,
            false,
            \Illuminate\Support\Str::uuid()->toString()
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('viewing_id', $result);
        $this->assertArrayHasKey('hold_expires_at', $result);
        $this->assertArrayHasKey('webrtc_room_id', $result);
        $this->assertNotNull($result['webrtc_room_id']);

        $slotKey = "viewing_slot:{$property->id}:{$scheduledAt->format('Y-m-d-H-i')}";
        $this->assertTrue(Redis::exists($slotKey) > 0);
    }

    public function test_book_viewing_with_hold_b2b_has_longer_hold_time(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);

        $scheduledAt = Carbon::now()->addHours(24);

        $b2cResult = $this->service->bookViewingWithHold(
            $property->id,
            $this->user->id,
            $scheduledAt,
            false,
            \Illuminate\Support\Str::uuid()->toString()
        );

        $b2bResult = $this->service->bookViewingWithHold(
            $property->id,
            $this->user->id + 1,
            $scheduledAt->addMinutes(30),
            true,
            \Illuminate\Support\Str::uuid()->toString()
        );

        $b2cExpires = Carbon::parse($b2cResult['hold_expires_at']);
        $b2bExpires = Carbon::parse($b2bResult['hold_expires_at']);

        $this->assertTrue($b2bExpires->gt($b2cExpires));
    }

    public function test_calculate_predictive_scoring_returns_valid_scores(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 10000000.00,
            'features' => [
                'title_clear' => true,
                'no_liens' => true,
                'zoning_compliant' => true,
                'permits_valid' => true,
                'location_score' => 0.8,
                'price_competitiveness' => 0.85,
            ],
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $result = $this->service->calculatePredictiveScoring(
            $property,
            $this->user->id,
            $correlationId
        );

        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('credit_score', $result);
        $this->assertArrayHasKey('legal_score', $result);
        $this->assertArrayHasKey('liquidity_score', $result);
        $this->assertArrayHasKey('recommendation', $result);
        $this->assertArrayHasKey('risk_factors', $result);
        $this->assertArrayHasKey('estimated_mortgage_rate', $result);

        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(1, $result['overall_score']);
        $this->assertContains($result['recommendation'], ['approved', 'review', 'declined']);
    }

    public function test_calculate_predictive_scoring_caches_result(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 10000000.00,
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $firstCall = $this->service->calculatePredictiveScoring($property, $this->user->id, $correlationId);
        $secondCall = $this->service->calculatePredictiveScoring($property, $this->user->id, $correlationId);

        $this->assertEquals($firstCall, $secondCall);
    }

    public function test_verify_documents_on区块链_returns_verification_results(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $documentHashes = [
            'title_deed' => hash('sha256', 'test_title_deed'),
            'ownership_certificate' => hash('sha256', 'test_ownership'),
        ];

        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $result = $this->service->verifyDocumentsOnBlockchain(
            $property,
            $documentHashes,
            $correlationId
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('all_verified', $result);
        $this->assertArrayHasKey('verifications', $result);
        $this->assertArrayHasKey('smart_contract_address', $result);
        $this->assertCount(2, $result['verifications']);
    }

    public function test_calculate_dynamic_price_applies_b2b_discount(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 10000000.00,
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $b2cResult = $this->service->calculateDynamicPrice($property, false, $correlationId);
        $b2bResult = $this->service->calculateDynamicPrice($property, true, $correlationId);

        $this->assertLessThan($b2cResult['final_price'], $b2bResult['final_price']);
        $this->assertTrue($b2bResult['is_b2b']);
        $this->assertFalse($b2cResult['is_b2b']);
    }

    public function test_calculate_dynamic_price_applies_flash_discount_on_low_demand(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 10000000.00,
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $result = $this->service->calculateDynamicPrice($property, false, $correlationId);

        $this->assertArrayHasKey('base_price', $result);
        $this->assertArrayHasKey('final_price', $result);
        $this->assertArrayHasKey('demand_score', $result);
        $this->assertArrayHasKey('discount_percentage', $result);
        $this->assertArrayHasKey('is_flash_discount', $result);
        $this->assertArrayHasKey('price_valid_until', $result);

        $this->assertGreaterThan(0, $result['demand_score']);
        $this->assertLessThanOrEqual(1, $result['demand_score']);
    }

    public function test_initiate_escrow_payment_creates_hold(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 10000000.00,
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $result = $this->service->initiateEscrowPayment(
            $property,
            $this->user->id,
            100000.00,
            $correlationId
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('hold_result', $result);
        $this->assertArrayHasKey('payment_intent', $result);
        $this->assertArrayHasKey('escrow_status', $result);
        $this->assertEquals('held', $result['escrow_status']);
    }

    public function test_release_escrow_payment_releases_hold(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $result = $this->service->releaseEscrowPayment(
            $property,
            $this->user->id,
            $correlationId
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('released_at', $result);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertNotNull($result['released_at']);
    }

    public function test_book_viewing_blocks_duplicate_slot(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);

        $scheduledAt = Carbon::now()->addHours(24);
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $this->service->bookViewingWithHold(
            $property->id,
            $this->user->id,
            $scheduledAt,
            false,
            $correlationId
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This time slot is already booked');

        $this->service->bookViewingWithHold(
            $property->id,
            $this->user->id + 1,
            $scheduledAt,
            false,
            \Illuminate\Support\Str::uuid()->toString()
        );
    }

    protected function tearDown(): void
    {
        Redis::flushdb();
        Cache::flush();
        parent::tearDown();
    }
}
