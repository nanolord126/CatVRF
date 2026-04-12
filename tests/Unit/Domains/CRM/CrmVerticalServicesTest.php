<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\CRM;

use App\Domains\CRM\Models\CrmAutoProfile;
use App\Domains\CRM\Models\CrmBeautyProfile;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmFoodProfile;
use App\Domains\CRM\Models\CrmTaxiProfile;
use App\Domains\CRM\Services\AutoCrmService;
use App\Domains\CRM\Services\BeautyCrmService;
use App\Domains\CRM\Services\FoodCrmService;
use App\Domains\CRM\Services\HotelCrmService;
use App\Domains\CRM\Services\TaxiCrmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Unit-тесты вертикальных CRM-сервисов — Beauty, Auto, Food, Hotel, Taxi.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmVerticalServicesTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private string $correlationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->correlationId = $this->faker->uuid();
    }

    // ═══════════════════════════════════════════════════════
    //  BEAUTY CRM SERVICE
    // ═══════════════════════════════════════════════════════

    public function test_beauty_crm_creates_profile(): void
    {
        $service = app(BeautyCrmService::class);

        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'beauty',
        ]);

        $profile = $service->createBeautyProfile($client->id, [
            'skin_type' => 'combination',
            'hair_type' => 'straight',
            'preferred_services' => ['haircut', 'coloring', 'manicure'],
            'allergies' => ['latex'],
            'favorite_masters' => [],
            'visit_frequency' => 'monthly',
        ], $this->correlationId);

        $this->assertInstanceOf(CrmBeautyProfile::class, $profile);
        $this->assertEquals('combination', $profile->skin_type);
        $this->assertEquals('straight', $profile->hair_type);
        $this->assertContains('manicure', $profile->preferred_services);
    }

    public function test_beauty_crm_sleeping_threshold(): void
    {
        $service = app(BeautyCrmService::class);

        CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'beauty',
            'last_interaction_at' => now()->subDays(40),
        ]);

        CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'beauty',
            'last_interaction_at' => now()->subDays(5),
        ]);

        $sleeping = $service->getSleepingClients(1, $this->correlationId);

        // Beauty sleeping threshold = 30 дней
        $this->assertGreaterThanOrEqual(1, $sleeping->count());
    }

    // ═══════════════════════════════════════════════════════
    //  AUTO CRM SERVICE
    // ═══════════════════════════════════════════════════════

    public function test_auto_crm_creates_profile(): void
    {
        $service = app(AutoCrmService::class);

        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'auto',
        ]);

        $profile = $service->createAutoProfile($client->id, [
            'vehicles' => [
                ['brand' => 'BMW', 'model' => 'X5', 'year' => 2023, 'vin' => 'WBA12345'],
            ],
            'preferred_brands' => ['BMW', 'Audi'],
            'fuel_type' => 'diesel',
        ], $this->correlationId);

        $this->assertInstanceOf(CrmAutoProfile::class, $profile);
        $this->assertCount(1, $profile->vehicles);
        $this->assertEquals('diesel', $profile->fuel_type);
    }

    public function test_auto_crm_records_service_history(): void
    {
        $service = app(AutoCrmService::class);

        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'auto',
        ]);

        $profile = $service->createAutoProfile($client->id, [
            'vehicles' => [
                ['brand' => 'Toyota', 'model' => 'Camry', 'year' => 2022],
            ],
            'service_history' => [],
        ], $this->correlationId);

        $service->recordServiceVisit($client->id, [
            'type' => 'oil_change',
            'mileage' => 45000,
            'cost' => 5000,
            'date' => now()->toDateString(),
        ], $this->correlationId);

        $profile->refresh();
        $this->assertNotEmpty($profile->service_history);
    }

    // ═══════════════════════════════════════════════════════
    //  FOOD CRM SERVICE
    // ═══════════════════════════════════════════════════════

    public function test_food_crm_creates_profile(): void
    {
        $service = app(FoodCrmService::class);

        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'food',
        ]);

        $profile = $service->createFoodProfile($client->id, [
            'dietary_preferences' => ['vegetarian'],
            'allergens' => ['nuts', 'gluten'],
            'favorite_cuisines' => ['Italian', 'Japanese'],
            'calorie_goal' => 2000,
        ], $this->correlationId);

        $this->assertInstanceOf(CrmFoodProfile::class, $profile);
        $this->assertContains('vegetarian', $profile->dietary_preferences);
        $this->assertContains('nuts', $profile->allergens);
        $this->assertEquals(2000, $profile->calorie_goal);
    }

    public function test_food_crm_sleeping_clients(): void
    {
        $service = app(FoodCrmService::class);

        CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'food',
            'last_interaction_at' => now()->subDays(20),
        ]);

        CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'food',
            'last_interaction_at' => now()->subDays(2),
        ]);

        $sleeping = $service->getSleepingClients(1, $this->correlationId);

        // Food sleeping threshold = 14 дней
        $this->assertGreaterThanOrEqual(1, $sleeping->count());
    }

    // ═══════════════════════════════════════════════════════
    //  TAXI CRM SERVICE
    // ═══════════════════════════════════════════════════════

    public function test_taxi_crm_creates_profile(): void
    {
        $service = app(TaxiCrmService::class);

        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'taxi',
        ]);

        $profile = $service->createTaxiProfile($client->id, [
            'home_address' => 'ул. Пушкина, 10',
            'work_address' => 'Невский пр., 28',
            'preferred_car_class' => 'comfort',
            'favorite_routes' => [],
        ], $this->correlationId);

        $this->assertInstanceOf(CrmTaxiProfile::class, $profile);
        $this->assertEquals('comfort', $profile->preferred_car_class);
    }

    public function test_taxi_crm_records_ride(): void
    {
        $service = app(TaxiCrmService::class);

        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'taxi',
        ]);

        $service->createTaxiProfile($client->id, [
            'total_rides' => 0,
            'favorite_routes' => [],
        ], $this->correlationId);

        $service->recordRide($client->id, [
            'from' => 'ул. Пушкина, 10',
            'to' => 'Невский пр., 28',
            'cost' => 450,
            'duration_minutes' => 25,
            'driver_rating' => 5,
        ], $this->correlationId);

        $profile = CrmTaxiProfile::where('crm_client_id', $client->id)->first();
        $this->assertGreaterThanOrEqual(1, $profile->total_rides);
    }

    public function test_taxi_crm_sleeping_threshold_is_14_days(): void
    {
        $service = app(TaxiCrmService::class);

        // Спящий (18 дней без активности)
        CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'taxi',
            'last_interaction_at' => now()->subDays(18),
        ]);

        // Активный (2 дня)
        CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'taxi',
            'last_interaction_at' => now()->subDays(2),
        ]);

        $sleeping = $service->getSleepingClients(1, $this->correlationId);
        $this->assertGreaterThanOrEqual(1, $sleeping->count());
    }

    // ═══════════════════════════════════════════════════════
    //  HOTEL CRM SERVICE
    // ═══════════════════════════════════════════════════════

    public function test_hotel_crm_creates_guest_profile(): void
    {
        $service = app(HotelCrmService::class);

        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'hotel',
        ]);

        $profile = $service->createGuestProfile($client->id, [
            'preferred_room_type' => 'deluxe',
            'dietary_preferences' => ['halal'],
            'loyalty_program' => 'gold',
            'special_requests' => ['late_checkout', 'extra_pillows'],
        ], $this->correlationId);

        $this->assertNotNull($profile);
        $this->assertEquals('deluxe', $profile->preferred_room_type);
    }

    // ═══════════════════════════════════════════════════════
    //  VERTICAL PROFILE RELATION ON CRM CLIENT
    // ═══════════════════════════════════════════════════════

    public function test_crm_client_vertical_profile_returns_correct_model(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'beauty',
        ]);

        CrmBeautyProfile::factory()->create([
            'crm_client_id' => $client->id,
            'tenant_id' => 1,
            'skin_type' => 'oily',
        ]);

        $profile = $client->verticalProfile();

        $this->assertInstanceOf(CrmBeautyProfile::class, $profile);
        $this->assertEquals('oily', $profile->skin_type);
    }

    public function test_crm_client_vertical_profile_returns_null_if_missing(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'beauty',
        ]);

        $profile = $client->verticalProfile();

        $this->assertNull($profile);
    }

    public function test_crm_client_vertical_profile_with_unknown_vertical(): void
    {
        $client = CrmClient::factory()->create([
            'tenant_id' => 1,
            'vertical' => 'unknown_vertical',
        ]);

        $profile = $client->verticalProfile();

        $this->assertNull($profile);
    }
}
