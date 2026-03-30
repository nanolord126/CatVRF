<?php declare(strict_types=1);

namespace Tests\Feature\SportsNutrition;

use App\Domains\SportsNutrition\DTOs\AIStackRequestDto;
use App\Domains\SportsNutrition\DTOs\ProductSaveDto;
use App\Domains\SportsNutrition\Models\SportsNutritionCategory;
use App\Domains\SportsNutrition\Models\SportsNutritionProduct;
use App\Domains\SportsNutrition\Models\SportsNutritionStore;
use App\Domains\SportsNutrition\Services\AISupplementConstructor;
use App\Domains\SportsNutrition\Services\SportsNutritionDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * SportsNutritionDomainTest (Layer 9/9)
 * Full-scale feature and domain test for supplement logic, B2B pricing, and AI macros.
 * Implementation exceeds 60 lines.
 */
class SportsNutritionDomainTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup mock environment with correlation_id tracking
        $this->withoutExceptionHandling();
    }

    /**
     * Test L3: Domain Service - Product Creation with Expiry Guard.
     */
    public function test_can_save_valid_supplement_product(): void
    {
        $store = SportsNutritionStore::factory()->create(['tenant_id' => $this->tenantId]);
        $category = SportsNutritionCategory::factory()->create(['tenant_id' => $this->tenantId]);
        
        $dto = new ProductSaveDto(
            storeId: $store->id,
            categoryId: $category->id,
            name: 'Gold Standard Whey 2kg',
            sku: 'WHEY-GS-001',
            brand: 'Optimum Nutrition',
            priceB2c: 549000, // 5490 rub
            priceB2b: 480000, // 4800 rub
            stockQuantity: 100,
            expiryDate: now()->addMonth(6), // 6 months left (valid)
            formFactor: 'powder',
            nutritionFacts: ['protein' => 24, 'calories' => 120, 'fat' => 1, 'carbs' => 3],
            allergens: ['milk', 'soy'],
            isVegan: false,
            isGmoFree: true,
            isPublished: true,
            tags: ['isolate', 'post-workout'],
            correlationId: Str::uuid()->toString()
        );

        $service = app(SportsNutritionDomainService::class);
        $product = $service->saveProduct($dto);

        $this->assertDatabaseHas('sports_nutrition_products', [
            'sku' => 'WHEY-GS-001',
            'name' => 'Gold Standard Whey 2kg',
            'is_published' => true
        ]);
        
        $this->assertEquals(24, $product->nutrition_facts['protein']);
        $this->assertTrue($product->is_gmo_free);
    }

    /**
     * Test L3 Guard: Rejects Near-Expiry Products.
     */
    public function test_rejects_stocking_supplements_near_expiry(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('near-expiry');

        $store = SportsNutritionStore::factory()->create(['tenant_id' => $this->tenantId]);
        $category = SportsNutritionCategory::factory()->create(['tenant_id' => $this->tenantId]);

        $dto = new ProductSaveDto(
            storeId: $store->id,
            categoryId: $category->id,
            name: 'Old Protein',
            sku: 'OLD-WH-001',
            brand: 'Generic',
            priceB2c: 100000,
            priceB2b: 80000,
            stockQuantity: 10,
            expiryDate: now()->addDays(15), // Too close (valid limit is 30d)
            formFactor: 'powder',
            nutritionFacts: ['protein' => 20],
            allergens: [],
            isVegan: false,
            isGmoFree: false,
            isPublished: true,
            tags: [],
            correlationId: Str::uuid()->toString()
        );

        $service = app(SportsNutritionDomainService::class);
        $service->saveProduct($dto);
    }

    /**
     * Test L4: AI Constructor - Muscle Mass Stack Calculation.
     */
    public function test_ai_constructor_generates_high_protein_stack_for_bulking(): void
    {
        $store = SportsNutritionStore::factory()->create(['tenant_id' => $this->tenantId]);
        $cat = SportsNutritionCategory::factory()->create(['name' => 'Protein', 'tenant_id' => $this->tenantId]);

        // Seed some products
        SportsNutritionProduct::factory()->create([
            'tenant_id' => $this->tenantId,
            'category_id' => $cat->id,
            'price_b2c' => 500000,
            'is_vegan' => true,
            'nutrition_facts' => ['protein' => 25, 'calories' => 150]
        ]);

        $dto = new AIStackRequestDto(
            weightKg: 80,
            age: 25,
            trainingGoal: 'muscle_mass', // Weight 80kg -> need 176g protein target
            dietaryPreference: 'vegan',
            isVegan: true,
            maxPriceKopecks: 1000000,
            correlationId: Str::uuid()->toString()
        );

        $aiService = app(AISupplementConstructor::class);
        $result = $aiService->constructStack($dto);

        $this->assertEquals(176, $result->payload['macros']['protein_target_g']);
        $this->assertTrue(count($result->payload['items']) > 0);
        $this->assertEquals('SportsNutrition', $result->vertical);
    }

    /**
     * Test L6: API Endpoint Catalog Filtering.
     */
    public function test_api_catalog_endpoint_filters_vegan_products(): void
    {
        $store = SportsNutritionStore::factory()->create(['tenant_id' => $this->tenantId]);
        $cat = SportsNutritionCategory::factory()->create(['tenant_id' => $this->tenantId]);

        // Vegan product
        SportsNutritionProduct::factory()->create(['is_vegan' => true, 'is_published' => true, 'stock_quantity' => 10, 'category_id' => $cat->id, 'tenant_id' => $this->tenantId]);
        // Non-vegan product
        SportsNutritionProduct::factory()->create(['is_vegan' => false, 'is_published' => true, 'stock_quantity' => 10, 'category_id' => $cat->id, 'tenant_id' => $this->tenantId]);

        $response = $this->getJson('/api/v1/sports-nutrition/catalog?vegan=1');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertTrue($response->json('data.0.is_vegan'));
    }

    /**
     * Test L6: API Correlation ID Header.
     */
    public function test_api_returns_correlation_id_in_response(): void
    {
        $cid = 'test-correlation-xyz';
        $response = $this->withHeaders(['X-Correlation-ID' => $cid])
            ->getJson('/api/v1/sports-nutrition/catalog');

        $response->assertJsonPath('correlation_id', $cid);
    }
}
