<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use App\Domains\Electronics\DTOs\AI\GadgetVisionAnalysisRequestDto;
use App\Domains\Electronics\DTOs\AI\GadgetVisionAnalysisResponseDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Services\AI\GadgetVisionRecommendationService;
use App\Services\FraudControlService;
use App\Services\RecommendationService;
use App\Services\UserTasteAnalyzerService;
use App\Services\ML\UserBehaviorAnalyzerService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OpenAI\Client as OpenAIClient;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Responses\Chat\CreateResponseChoice;
use OpenAI\Responses\Chat\CreateResponseMessage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GadgetVisionRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private GadgetVisionRecommendationService $service;
    private FraudControlService $fraud;
    private RecommendationService $recommendation;
    private UserTasteAnalyzerService $tasteAnalyzer;
    private UserBehaviorAnalyzerService $behaviorAnalyzer;
    private Cache $cache;
    private DatabaseManager $db;
    private OpenAIClient $openai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->createMock(FraudControlService::class);
        $this->fraud->method('check')->willReturn(null);

        $this->recommendation = $this->createMock(RecommendationService::class);
        $this->tasteAnalyzer = $this->createMock(UserTasteAnalyzerService::class);
        $this->behaviorAnalyzer = $this->createMock(UserBehaviorAnalyzerService::class);
        $this->cache = app(Cache::class);
        $this->db = app(DatabaseManager::class);
        $this->openai = $this->createMock(OpenAIClient::class);

        $this->service = new GadgetVisionRecommendationService(
            $this->fraud,
            $this->recommendation,
            $this->tasteAnalyzer,
            $this->behaviorAnalyzer,
            $this->cache,
            $this->db,
            $this->openai,
            app('log'),
        );

        Storage::fake('public');
    }

    #[Test]
    public function it_analyzes_photo_and_returns_recommendations(): void
    {
        $userId = 1;
        $correlationId = 'test-correlation-123';
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        $this->tasteAnalyzer->method('getProfile')->willReturn((object) ['preferences' => ['brands' => ['Apple']]]);

        $this->behaviorAnalyzer->method('classifyUser')->willReturn('returning');

        $this->recommendation->method('getForVertical')->willReturn(collect());

        $mockResponse = $this->createMock(CreateResponse::class);
        $mockChoice = $this->createMock(CreateResponseChoice::class);
        $mockMessage = $this->createMock(CreateResponseMessage::class);

        $mockMessage->content = json_encode([
            'detected_device' => 'smartphone',
            'features' => ['5G', 'OLED display'],
            'estimated_price_range' => '50000-100000',
            'compatible_accessories' => ['case', 'charger'],
            'technical_requirements' => [],
            'confidence_score' => 0.95,
        ]);

        $mockChoice->message = $mockMessage;
        $mockResponse->choices = [$mockChoice];

        $this->openai->method('chat')->willReturnSelf();
        $this->openai->method('create')->willReturn($mockResponse);

        ElectronicsProduct::factory()->create([
            'name' => 'iPhone 15 Pro',
            'brand' => 'Apple',
            'category' => 'smartphones',
            'price_kopecks' => 9999900,
            'original_price_kopecks' => 9999900,
            'availability_status' => 'in_stock',
            'is_active' => true,
            'stock_quantity' => 10,
            'rating' => 4.8,
            'reviews_count' => 150,
        ]);

        $dto = new GadgetVisionAnalysisRequestDto(
            image: $image,
            userId: $userId,
            correlationId: $correlationId,
            budgetMaxKopecks: 15000000,
            analysisType: 'gadget_recommendation',
            preferredBrands: ['Apple'],
            useCases: ['photography'],
            additionalSpecs: [],
            idempotencyKey: null,
        );

        $result = $this->service->analyzePhotoAndRecommend($dto);

        $this->assertInstanceOf(GadgetVisionAnalysisResponseDto::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals($correlationId, $result->correlationId);
        $this->assertIsArray($result->visionAnalysis);
        $this->assertIsArray($result->recommendedProducts);
        $this->assertIsArray($result->arPreviewUrls);
        $this->assertIsArray($result->pricingInfo);
        $this->assertArrayHasKey('base_total_kopecks', $result->pricingInfo);
        $this->assertArrayHasKey('final_total_kopecks', $result->pricingInfo);
        $this->assertArrayHasKey('discount_percentage', $result->pricingInfo);
    }

    #[Test]
    public function it_returns_cached_result_on_duplicate_request(): void
    {
        $userId = 1;
        $correlationId = 'test-correlation-456';
        $image = UploadedFile::fake()->image('test.jpg');

        $this->fraud->expects($this->once())->method('check');
        $this->openai->expects($this->once())->method('create');

        $this->tasteAnalyzer->method('getProfile')->willReturn((object) ['preferences' => []]);
        $this->behaviorAnalyzer->method('classifyUser')->willReturn('new');
        $this->recommendation->method('getForVertical')->willReturn(collect());

        $mockResponse = $this->createMock(CreateResponse::class);
        $mockChoice = $this->createMock(CreateResponseChoice::class);
        $mockMessage = $this->createMock(CreateResponseMessage::class);
        $mockMessage->content = json_encode([
            'detected_device' => 'laptop',
            'features' => [],
            'estimated_price_range' => '50000-150000',
            'compatible_accessories' => [],
            'technical_requirements' => [],
            'confidence_score' => 0.8,
        ]);
        $mockChoice->message = $mockMessage;
        $mockResponse->choices = [$mockChoice];
        $this->openai->method('chat')->willReturnSelf();
        $this->openai->method('create')->willReturn($mockResponse);

        ElectronicsProduct::factory()->create([
            'name' => 'MacBook Pro',
            'brand' => 'Apple',
            'category' => 'laptops',
            'price_kopecks' => 19999900,
            'original_price_kopecks' => 19999900,
            'availability_status' => 'in_stock',
            'is_active' => true,
            'stock_quantity' => 5,
            'rating' => 4.9,
            'reviews_count' => 200,
        ]);

        $dto = new GadgetVisionAnalysisRequestDto(
            image: $image,
            userId: $userId,
            correlationId: $correlationId,
            budgetMaxKopecks: 25000000,
            analysisType: 'gadget_recommendation',
            preferredBrands: [],
            useCases: [],
            additionalSpecs: [],
            idempotencyKey: null,
        );

        $firstResult = $this->service->analyzePhotoAndRecommend($dto);
        $secondResult = $this->service->analyzePhotoAndRecommend($dto);

        $this->assertEquals($firstResult->correlationId, $secondResult->correlationId);
    }

    #[Test]
    public function it_calculates_dynamic_pricing_for_returning_users(): void
    {
        $userId = 1;

        $this->tasteAnalyzer->method('getProfile')->willReturn((object) ['preferences' => []]);
        $this->behaviorAnalyzer->method('classifyUser')->willReturn('returning');
        $this->recommendation->method('getForVertical')->willReturn(collect());

        $mockResponse = $this->createMock(CreateResponse::class);
        $mockChoice = $this->createMock(CreateResponseChoice::class);
        $mockMessage = $this->createMock(CreateResponseMessage::class);
        $mockMessage->content = json_encode([
            'detected_device' => 'tablet',
            'features' => [],
            'estimated_price_range' => '30000-80000',
            'compatible_accessories' => [],
            'technical_requirements' => [],
            'confidence_score' => 0.85,
        ]);
        $mockChoice->message = $mockMessage;
        $mockResponse->choices = [$mockChoice];
        $this->openai->method('chat')->willReturnSelf();
        $this->openai->method('create')->willReturn($mockResponse);

        ElectronicsProduct::factory()->create([
            'name' => 'iPad Pro',
            'brand' => 'Apple',
            'category' => 'tablets',
            'price_kopecks' => 8999900,
            'original_price_kopecks' => 8999900,
            'availability_status' => 'in_stock',
            'is_active' => true,
            'stock_quantity' => 8,
            'rating' => 4.7,
            'reviews_count' => 120,
        ]);

        $image = UploadedFile::fake()->image('test.jpg');
        $dto = new GadgetVisionAnalysisRequestDto(
            image: $image,
            userId: $userId,
            correlationId: 'test-789',
            budgetMaxKopecks: 15000000,
            analysisType: 'gadget_recommendation',
            preferredBrands: [],
            useCases: [],
            additionalSpecs: [],
            idempotencyKey: null,
        );

        $result = $this->service->analyzePhotoAndRecommend($dto);

        $this->assertGreaterThan(0, $result->pricingInfo['discount_percentage']);
        $this->assertGreaterThan(0, $result->pricingInfo['savings_kopecks']);
    }

    #[Test]
    public function it_handles_vision_api_failure_with_fallback(): void
    {
        $userId = 1;

        $this->tasteAnalyzer->method('getProfile')->willReturn((object) ['preferences' => []]);
        $this->behaviorAnalyzer->method('classifyUser')->willReturn('new');
        $this->recommendation->method('getForVertical')->willReturn(collect());

        $this->openai->method('chat')->willThrowException(new \Exception('API Error'));

        ElectronicsProduct::factory()->create([
            'name' => 'Samsung Galaxy S24',
            'brand' => 'Samsung',
            'category' => 'smartphones',
            'price_kopecks' => 7999900,
            'original_price_kopecks' => 7999900,
            'availability_status' => 'in_stock',
            'is_active' => true,
            'stock_quantity' => 15,
            'rating' => 4.6,
            'reviews_count' => 80,
        ]);

        $image = UploadedFile::fake()->image('test.jpg');
        $dto = new GadgetVisionAnalysisRequestDto(
            image: $image,
            userId: $userId,
            correlationId: 'test-error',
            budgetMaxKopecks: 10000000,
            analysisType: 'gadget_recommendation',
            preferredBrands: [],
            useCases: [],
            additionalSpecs: [],
            idempotencyKey: null,
        );

        $result = $this->service->analyzePhotoAndRecommend($dto);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('fallback_analysis', $result->visionAnalysis);
    }

    #[Test]
    public function it_saves_analysis_result_to_database(): void
    {
        $userId = 1;
        $correlationId = 'test-save-123';

        $this->tasteAnalyzer->method('getProfile')->willReturn((object) ['preferences' => []]);
        $this->behaviorAnalyzer->method('classifyUser')->willReturn('new');
        $this->recommendation->method('getForVertical')->willReturn(collect());

        $mockResponse = $this->createMock(CreateResponse::class);
        $mockChoice = $this->createMock(CreateResponseChoice::class);
        $mockMessage = $this->createMock(CreateResponseMessage::class);
        $mockMessage->content = json_encode([
            'detected_device' => 'smartwatch',
            'features' => [],
            'estimated_price_range' => '20000-50000',
            'compatible_accessories' => [],
            'technical_requirements' => [],
            'confidence_score' => 0.75,
        ]);
        $mockChoice->message = $mockMessage;
        $mockResponse->choices = [$mockChoice];
        $this->openai->method('chat')->willReturnSelf();
        $this->openai->method('create')->willReturn($mockResponse);

        ElectronicsProduct::factory()->create([
            'name' => 'Apple Watch Series 9',
            'brand' => 'Apple',
            'category' => 'smartwatches',
            'price_kopecks' => 4499900,
            'original_price_kopecks' => 4499900,
            'availability_status' => 'in_stock',
            'is_active' => true,
            'stock_quantity' => 20,
            'rating' => 4.5,
            'reviews_count' => 90,
        ]);

        $image = UploadedFile::fake()->image('test.jpg');
        $dto = new GadgetVisionAnalysisRequestDto(
            image: $image,
            userId: $userId,
            correlationId: $correlationId,
            budgetMaxKopecks: 5000000,
            analysisType: 'gadget_recommendation',
            preferredBrands: [],
            useCases: [],
            additionalSpecs: [],
            idempotencyKey: null,
        );

        $this->service->analyzePhotoAndRecommend($dto);

        $this->assertDatabaseHas('user_ai_designs', [
            'user_id' => $userId,
            'vertical' => 'electronics',
            'correlation_id' => $correlationId,
        ]);
    }
}
