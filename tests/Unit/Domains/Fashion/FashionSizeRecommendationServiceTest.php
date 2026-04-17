<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion;

use App\Domains\Fashion\Services\FashionSizeRecommendationService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\BaseTestCase;

final class FashionSizeRecommendationServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private FashionSizeRecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FashionSizeRecommendationService(
            $this->app->make(AuditService::class),
            $this->app->make(FraudControlService::class),
            $this->app->make('Illuminate\Database\DatabaseManager'),
        );
    }

    public function test_recommend_size_returns_structure(): void
    {
        $userId = $this->createUser();
        $productId = $this->createFashionProduct();

        $result = $this->service->recommendSize($userId, $productId, null, 'test-123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('product_id', $result);
        $this->assertArrayHasKey('recommended_size', $result);
        $this->assertArrayHasKey('confidence', $result);
    }

    public function test_update_user_size_profile(): void
    {
        $userId = $this->createUser();
        $measurements = ['height' => 175, 'weight' => 70, 'chest' => 100, 'waist' => 85];

        $result = $this->service->updateUserSizeProfile($userId, $measurements, 'test-123');

        $this->assertIsArray($result);
        $this->assertTrue($result['profile_updated']);
        $this->assertDatabaseHas('fashion_user_size_profiles', [
            'user_id' => $userId,
            'height' => 175,
        ]);
    }

    public function test_get_user_size_history(): void
    {
        $userId = $this->createUser();

        $result = $this->service->getUserSizeHistory($userId, 'test-123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('size_preferences', $result);
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
