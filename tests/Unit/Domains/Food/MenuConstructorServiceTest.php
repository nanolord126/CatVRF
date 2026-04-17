<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Food;

use App\Domains\Food\Services\AI\MenuConstructorService;
use App\Services\RecommendationService;
use App\Services\InventoryService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MenuConstructorServiceTest extends TestCase
{
    use RefreshDatabase;

    private MenuConstructorService $service;
    private RecommendationService $recommendation;
    private InventoryService $inventory;
    private UserTasteAnalyzerService $tasteAnalyzer;
    private FraudControlService $fraud;
    private AuditService $audit;
    private CacheRepository $cache;
    private DatabaseManager $db;
    private Guard $guard;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recommendation = $this->createMock(RecommendationService::class);
        $this->inventory = $this->createMock(InventoryService::class);
        $this->tasteAnalyzer = $this->createMock(UserTasteAnalyzerService::class);
        $this->fraud = $this->createMock(FraudControlService::class);
        $this->audit = $this->createMock(AuditService::class);
        $this->cache = $this->app->make(CacheRepository::class);
        $this->db = $this->app->make(DatabaseManager::class);
        $this->guard = $this->createMock(Guard::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new MenuConstructorService(
            $this->recommendation,
            $this->inventory,
            $this->tasteAnalyzer,
            $this->fraud,
            $this->audit,
            $this->cache,
            $this->db,
            $this->logger,
            $this->guard
        );
    }

    public function test_generate_menu_returns_success(): void
    {
        // Arrange
        $this->fraud->expects($this->once())
            ->method('check')
            ->with([
                'user_id' => 1,
                'operation_type' => 'food_ai_constructor',
                'correlation_id' => $this->anything(),
            ]);

        $this->tasteAnalyzer->expects($this->once())
            ->method('getProfile')
            ->with(1)
            ->willReturn((object) ['food_preferences' => ['cuisine' => 'italian']]);

        $this->recommendation->expects($this->once())
            ->method('getForUser')
            ->willReturn([
                ['product_id' => 1, 'name' => 'Pasta'],
                ['product_id' => 2, 'name' => 'Pizza'],
            ]);

        $this->inventory->expects($this->exactly(2))
            ->method('getAvailableStock')
            ->willReturn(15);

        $this->audit->expects($this->once())
            ->method('record');

        $preferences = [
            'diet' => 'balanced',
            'min_calories' => 1500,
            'max_calories' => 2000,
            'ingredients' => ['tomato', 'cheese'],
        ];

        // Act
        $result = $this->service->generateMenu($preferences, 1, 'test-correlation-id');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('food', $result['vertical']);
        $this->assertIsArray($result['menu']);
        $this->assertIsArray($result['recommendations']);
        $this->assertArrayHasKey('total_calories', $result);
    }

    public function test_generate_menu_uses_cache(): void
    {
        // Arrange
        $this->fraud->expects($this->once())
            ->method('check');

        $this->tasteAnalyzer->expects($this->once())
            ->method('getProfile')
            ->willReturn((object) ['food_preferences' => []]);

        $this->recommendation->expects($this->once())
            ->method('getForUser')
            ->willReturn([]);

        $this->audit->expects($this->once())
            ->method('record');

        $preferences = [
            'diet' => 'balanced',
            'min_calories' => 1500,
            'max_calories' => 2000,
            'ingredients' => [],
        ];

        // Act - First call
        $result1 = $this->service->generateMenu($preferences, 1, 'test-correlation-id');

        // Act - Second call (should use cache)
        $result2 = $this->service->generateMenu($preferences, 1, 'test-correlation-id');

        // Assert
        $this->assertEquals($result1, $result2);
    }

    public function test_generate_menu_includes_user_taste_preferences(): void
    {
        // Arrange
        $this->fraud->expects($this->once())
            ->method('check');

        $this->tasteAnalyzer->expects($this->once())
            ->method('getProfile')
            ->with(1)
            ->willReturn((object) ['food_preferences' => ['cuisine' => 'mexican', 'spice_level' => 'hot']]);

        $this->recommendation->expects($this->once())
            ->method('getForUser')
            ->willReturn([]);

        $this->audit->expects($this->once())
            ->method('record');

        $preferences = ['diet' => 'balanced'];

        // Act
        $result = $this->service->generateMenu($preferences, 1, 'test-correlation-id');

        // Assert
        $this->assertTrue($result['success']);
    }
}
