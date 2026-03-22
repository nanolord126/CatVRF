<?php declare(strict_types=1);

namespace Tests\Feature\Domains\RealEstate;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\SaleListing;
use App\Domains\RealEstate\Models\RentalListing;
use App\Domains\RealEstate\Services\PropertyService;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\BaseTestCase;

/**
 * PropertyServiceTest — Feature-тесты вертикали Недвижимость.
 */
final class PropertyServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private PropertyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PropertyService::class);
        $this->app->instance(
            FraudControlService::class,
            \Mockery::mock(FraudControlService::class)->shouldReceive('check')->andReturn(true)->getMock()
        );
    }

    public function test_property_created_with_required_fields(): void
    {
        $property = $this->service->createProperty([
            'owner_id'       => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'address'        => 'ул. Тверская, 1, Москва',
            'type'           => 'apartment',
            'area'           => 65,
            'rooms'          => 2,
            'floor'          => 5,
            'price'          => 12_000_000_00, // in kopecks
            'status'         => 'active',
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $this->assertInstanceOf(Property::class, $property);
        $this->assertNotNull($property->uuid);
        $this->assertNotNull($property->correlation_id);
        $this->assertSame($this->tenant->id, $property->tenant_id);
    }

    public function test_rental_listing_requires_rent_price(): void
    {
        $property = Property::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->createRentalListing($property->id, [
            'rent_price_month' => 0, // Invalid
            'deposit'          => 50_000_00,
            'lease_term_min'   => 6,
        ]);
    }

    public function test_sale_commission_14_percent(): void
    {
        $property = Property::factory()->create(['tenant_id' => $this->tenant->id]);

        $listing = $this->service->createSaleListing($property->id, [
            'sale_price'        => 10_000_000_00,
            'commission_percent' => 14,
        ]);

        $this->assertSame(14, $listing->commission_percent);
        $this->assertDatabaseHas('sale_listings', [
            'property_id'       => $property->id,
            'commission_percent' => 14,
        ]);
    }

    public function test_property_closed_after_sale(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'active',
        ]);

        $this->service->markAsSold($property->id, Str::uuid()->toString());

        $property->refresh();
        $this->assertSame('sold', $property->status);
    }

    public function test_tenant_scoping_on_property_list(): void
    {
        Property::factory()->create(['tenant_id' => $this->tenant->id]);
        $other = \App\Models\Tenant::factory()->create();
        Property::factory()->create(['tenant_id' => $other->id]);

        $properties = $this->service->listProperties($this->tenant->id);

        foreach ($properties as $p) {
            $this->assertSame($this->tenant->id, $p->tenant_id);
        }
    }

    public function test_viewing_appointment_creates_record(): void
    {
        $property = Property::factory()->create(['tenant_id' => $this->tenant->id]);

        $appointment = $this->service->bookViewing([
            'property_id'    => $property->id,
            'client_id'      => $this->user->id,
            'datetime'       => now()->addDays(3)->toDateTimeString(),
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $this->assertDatabaseHas('viewing_appointments', [
            'property_id' => $property->id,
            'client_id'   => $this->user->id,
        ]);
    }
}
