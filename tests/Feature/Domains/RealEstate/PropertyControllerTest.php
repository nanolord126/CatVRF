<?php declare(strict_types=1);

namespace Tests\Feature\Domains\RealEstate;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\RentalListing;
use App\Domains\RealEstate\Models\SaleListing;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * PropertyControllerTest — Feature-тесты HTTP-слоя вертикали Недвижимость.
 *
 * Покрывает:
 * - PropertyController: store/update/destroy
 * - RentalListingController: store/destroy
 * - SaleListingController: store/destroy
 * - Fraud-block (403), аудит-лог, валидация (422)
 */
final class PropertyControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user   = User::factory()->create(['tenant_id' => $this->tenant->id]);

        app()->bind('tenant', fn () => $this->tenant);
        $this->actingAs($this->user);
    }

    // =========================================================================
    // PropertyController::store
    // =========================================================================

    /** @test */
    public function property_store_succeeds_with_audit_log(): void
    {
        $this->allowFraud();
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->once();

        $this->postJson('/api/v1/real-estate/properties', [
            'address'    => 'ул. Арбат, 1',
            'type'       => 'apartment',
            'area'       => 55,
            'rooms'      => 2,
            'floor'      => 4,
            'price'      => 900_000_000,
            'tenant_id'  => $this->tenant->id,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data.id', 'correlation_id']);

        $this->assertDatabaseHas('properties', ['address' => 'ул. Арбат, 1']);
    }

    /** @test */
    public function property_store_returns_403_when_fraud_detected(): void
    {
        $this->blockFraud();
        Log::shouldReceive('channel')->with('fraud_alert')->once()->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $this->postJson('/api/v1/real-estate/properties', [
            'address'   => 'ул. Тестовая, 99',
            'type'      => 'house',
            'area'      => 100,
            'rooms'     => 4,
            'floor'     => 1,
            'price'     => 500_000_000,
            'tenant_id' => $this->tenant->id,
        ])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['correlation_id']);
    }

    /** @test */
    public function property_store_returns_422_without_required_fields(): void
    {
        $this->allowFraud();

        $this->postJson('/api/v1/real-estate/properties', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function property_update_logs_before_and_after_state(): void
    {
        $this->allowFraud();

        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price'     => 800_000_000,
        ]);

        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->once()->with(\Mockery::type('string'), \Mockery::on(fn ($ctx) => isset($ctx['before'], $ctx['after'])));

        $this->putJson("/api/v1/real-estate/properties/{$property->id}", ['price' => 900_000_000])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('properties', ['id' => $property->id, 'price' => 900_000_000]);
    }

    /** @test */
    public function property_destroy_deletes_and_logs(): void
    {
        $this->allowFraud();

        $property = Property::factory()->create(['tenant_id' => $this->tenant->id]);

        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->once();

        $this->deleteJson("/api/v1/real-estate/properties/{$property->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('properties', ['id' => $property->id]);
    }

    // =========================================================================
    // RentalListingController
    // =========================================================================

    /** @test */
    public function rental_listing_store_succeeds_and_logs(): void
    {
        $this->allowFraud();

        $property = Property::factory()->create(['tenant_id' => $this->tenant->id]);

        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->once();

        $this->postJson('/api/v1/real-estate/rental-listings', [
            'property_id'     => $property->id,
            'rent_price_month' => 80_000_00,
            'deposit'          => 80_000_00,
            'lease_term_min'   => 6,
            'tenant_id'        => $this->tenant->id,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('rental_listings', ['property_id' => $property->id]);
    }

    /** @test */
    public function rental_listing_destroy_blocks_when_fraud(): void
    {
        $this->blockFraud();

        $property = Property::factory()->create(['tenant_id' => $this->tenant->id]);
        $listing  = RentalListing::factory()->create(['property_id' => $property->id, 'tenant_id' => $this->tenant->id]);

        $this->deleteJson("/api/v1/real-estate/rental-listings/{$listing->id}")
            ->assertStatus(403);
    }

    // =========================================================================
    // SaleListingController
    // =========================================================================

    /** @test */
    public function sale_listing_store_succeeds_and_logs(): void
    {
        $this->allowFraud();

        $property = Property::factory()->create(['tenant_id' => $this->tenant->id]);

        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->once();

        $this->postJson('/api/v1/real-estate/sale-listings', [
            'property_id'       => $property->id,
            'sale_price'        => 12_000_000_00,
            'commission_percent' => 2.5,
            'tenant_id'          => $this->tenant->id,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('sale_listings', ['property_id' => $property->id]);
    }

    /** @test */
    public function sale_listing_destroy_blocks_when_fraud(): void
    {
        $this->blockFraud();

        $property = Property::factory()->create(['tenant_id' => $this->tenant->id]);
        $listing  = SaleListing::factory()->create(['property_id' => $property->id, 'tenant_id' => $this->tenant->id]);

        $this->deleteJson("/api/v1/real-estate/sale-listings/{$listing->id}")
            ->assertStatus(403);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function allowFraud(): void
    {
        $mock = $this->createMock(FraudControlService::class);
        $mock->method('check')->willReturn(['score' => 0.05, 'decision' => 'allow']);
        $this->app->instance(FraudControlService::class, $mock);
    }

    private function blockFraud(): void
    {
        $mock = $this->createMock(FraudControlService::class);
        $mock->method('check')->willReturn(['score' => 0.97, 'decision' => 'block']);
        $this->app->instance(FraudControlService::class, $mock);
    }
}
