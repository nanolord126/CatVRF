<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion;

use App\Domains\Fashion\Services\FashionCollaborativeFilteringService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\BaseTestCase;

final class FashionCollaborativeFilteringServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private FashionCollaborativeFilteringService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FashionCollaborativeFilteringService(
            $this->app->make(AuditService::class),
            $this->app->make(FraudControlService::class),
            $this->app->make('Illuminate\Database\DatabaseManager'),
        );
    }

    public function test_get_recommendations_returns_structure(): void
    {
        $userId = $this->createUser();
        $this->createFashionProduct();

        $result = $this->service->getRecommendations(
            userId: $userId,
            algorithm: 'hybrid',
            limit: 10,
            correlationId: 'test-123'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('algorithm', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertIsArray($result['recommendations']);
    }

    public function test_update_user_latent_factors(): void
    {
        $userId = $this->createUser();
        $tenantId = 1;

        $this->service->updateUserLatentFactors($userId, $tenantId);

        $this->assertDatabaseHas('fashion_user_latent_factors', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ]);
    }

    public function test_update_item_latent_factors(): void
    {
        $productId = $this->createFashionProduct();
        $tenantId = 1;

        $this->service->updateItemLatentFactors($productId, $tenantId);

        $this->assertDatabaseHas('fashion_item_latent_factors', [
            'product_id' => $productId,
            'tenant_id' => $tenantId,
        ]);
    }

    public function test_calculate_cosine_similarity(): void
    {
        $vector1 = [1.0, 2.0, 3.0];
        $vector2 = [1.0, 2.0, 3.0];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateCosineSimilarity');
        $method->setAccessible(true);

        $similarity = $method->invoke($this->service, $vector1, $vector2);

        $this->assertEquals(1.0, $similarity);
    }

    private function createUser(): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createFashionProduct(array $overrides = []): int
    {
        return DB::table('fashion_products')->insertGetId(array_merge([
            'tenant_id' => 1,
            'fashion_store_id' => 1,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'brand' => 'Test Brand',
            'color' => 'black',
            'price_b2c' => 1000,
            'stock_quantity' => 10,
            'status' => 'active',
            'correlation_id' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
