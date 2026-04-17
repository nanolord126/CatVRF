<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Beauty;

use App\Domains\Beauty\Services\AI\BeautyImageConstructorService;
use App\Services\RecommendationService;
use App\Services\InventoryService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Filesystem\Factory as StorageFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BeautyImageConstructorServiceTest extends TestCase
{
    use RefreshDatabase;

    private BeautyImageConstructorService $service;
    private RecommendationService $recommendation;
    private InventoryService $inventory;
    private UserTasteAnalyzerService $tasteAnalyzer;
    private FraudControlService $fraud;
    private AuditService $audit;
    private LogManager $logger;
    private DatabaseManager $db;
    private StorageFactory $storage;
    private CacheRepository $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recommendation = $this->createMock(RecommendationService::class);
        $this->inventory = $this->createMock(InventoryService::class);
        $this->tasteAnalyzer = $this->createMock(UserTasteAnalyzerService::class);
        $this->fraud = $this->createMock(FraudControlService::class);
        $this->audit = $this->createMock(AuditService::class);
        $this->logger = $this->createMock(LogManager::class);
        $this->db = $this->app->make(DatabaseManager::class);
        $this->storage = $this->createMock(StorageFactory::class);
        $this->cache = $this->app->make(CacheRepository::class);

        $this->service = new BeautyImageConstructorService(
            $this->recommendation,
            $this->inventory,
            $this->tasteAnalyzer,
            $this->fraud,
            $this->audit,
            $this->logger,
            $this->db,
            $this->storage,
            $this->cache
        );
    }

    public function test_analyze_photo_and_recommend_returns_success(): void
    {
        // Arrange
        $this->fraud->expects($this->once())
            ->method('check')
            ->with([
                'user_id' => 1,
                'operation_type' => 'beauty_ai_constructor',
                'correlation_id' => $this->anything(),
            ]);

        $this->tasteAnalyzer->expects($this->once())
            ->method('getProfile')
            ->with(1)
            ->willReturn((object) ['beauty_preferences' => ['style' => 'modern']]);

        $this->recommendation->expects($this->once())
            ->method('getForBeauty')
            ->willReturn([
                ['product_id' => 1, 'name' => 'Lipstick'],
                ['product_id' => 2, 'name' => 'Foundation'],
            ]);

        $this->inventory->expects($this->exactly(2))
            ->method('getAvailableStock')
            ->willReturn(10);

        $this->audit->expects($this->once())
            ->method('record');

        $photo = UploadedFile::fake()->image('photo.jpg');

        // Act
        $result = $this->service->analyzePhotoAndRecommend($photo, 1, 'test-correlation-id');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('beauty', $result['vertical']);
        $this->assertIsArray($result['payload']);
        $this->assertIsArray($result['suggestions']);
        $this->assertEquals(0.95, $result['confidence_score']);
    }

    public function test_analyze_photo_and_recommend_uses_cache(): void
    {
        // Arrange
        $this->fraud->expects($this->once())
            ->method('check');

        $this->tasteAnalyzer->expects($this->once())
            ->method('getProfile')
            ->willReturn((object) ['beauty_preferences' => []]);

        $this->recommendation->expects($this->once())
            ->method('getForBeauty')
            ->willReturn([]);

        $this->audit->expects($this->once())
            ->method('record');

        $photo = UploadedFile::fake()->image('photo.jpg');

        // Act - First call
        $result1 = $this->service->analyzePhotoAndRecommend($photo, 1, 'test-correlation-id');

        // Act - Second call (should use cache)
        $result2 = $this->service->analyzePhotoAndRecommend($photo, 1, 'test-correlation-id');

        // Assert
        $this->assertEquals($result1, $result2);
    }

    public function test_analyze_photo_and_recommend_throws_exception_for_invalid_file_type(): void
    {
        // Arrange
        $this->fraud->expects($this->once())
            ->method('check');

        $photo = UploadedFile::fake()->create('document.pdf', 100);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file type for Beauty Scan.');

        $this->service->analyzePhotoAndRecommend($photo, 1, 'test-correlation-id');
    }
}
