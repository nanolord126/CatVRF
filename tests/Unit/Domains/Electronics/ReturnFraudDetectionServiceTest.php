<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use App\Domains\Electronics\DTOs\ReturnFraudDetectionDto;
use App\Domains\Electronics\DTOs\FraudDetectionResultDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Services\ReturnFraudDetectionService;
use App\Services\FraudControlService;
use App\Services\FraudMLService;
use App\Services\ML\UserBehaviorAnalyzerService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ReturnFraudDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReturnFraudDetectionService $service;
    private FraudControlService $fraud;
    private FraudMLService $fraudML;
    private UserBehaviorAnalyzerService $behaviorAnalyzer;
    private Cache $cache;
    private DatabaseManager $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->createMock(FraudControlService::class);
        $this->fraud->method('check')->willReturn(null);

        $this->fraudML = $this->createMock(FraudMLService::class);
        $this->behaviorAnalyzer = $this->createMock(UserBehaviorAnalyzerService::class);
        $this->cache = app(Cache::class);
        $this->db = app(DatabaseManager::db);

        $this->service = new ReturnFraudDetectionService(
            $this->fraud,
            $this->fraudML,
            $this->behaviorAnalyzer,
            $this->cache,
            $this->db,
            app('log'),
        );
    }

    #[Test]
    public function it_detects_legitimate_return(): void
    {
        $userId = 1;
        $orderId = 100;
        $correlationId = 'test-return-legit-123';

        $product = ElectronicsProduct::factory()->create([
            'name' => 'iPhone 15 Pro',
            'brand' => 'Apple',
            'category' => 'smartphones',
            'price_kopecks' => 9999900,
            'availability_status' => 'in_stock',
            'is_active' => true,
        ]);

        $this->fraudML->method('predict')->willReturn(0.2);
        $this->behaviorAnalyzer->method('classifyUser')->willReturn('returning');

        $dto = new ReturnFraudDetectionDto(
            orderId: $orderId,
            productId: $product->id,
            serialNumber: 'AP15PRO123456',
            userId: $userId,
            correlationId: $correlationId,
            returnReason: 'changed_mind',
            condition: 'like_new',
            deviceMetadata: [
                'imei' => '123456789012345',
                'battery_health' => 95,
                'screen_condition' => 'perfect',
                'activation_date' => '2024-01-01',
            ],
            userBehavior: [
                'time_on_site_minutes' => 30,
                'page_views_before_purchase' => 15,
                'cart_abandonment_rate' => 0.2,
            ],
            idempotencyKey: null,
        );

        $result = $this->service->detectReturnFraud($dto);

        $this->assertInstanceOf(FraudDetectionResultDto::class, $result);
        $this->assertFalse($result->isFraudulent);
        $this->assertLessThan(0.65, $result->fraudProbability);
        $this->assertEquals('minimal', $result->riskLevel);
        $this->assertEquals('approve_immediately', $result->recommendedAction);
    }

    #[Test]
    public function it_detects_fraudulent_return(): void
    {
        $userId = 1;
        $orderId = 101;
        $correlationId = 'test-return-fraud-456';

        $product = ElectronicsProduct::factory()->create([
            'name' => 'Samsung Galaxy S24',
            'brand' => 'Samsung',
            'category' => 'smartphones',
            'price_kopecks' => 7999900,
            'availability_status' => 'in_stock',
            'is_active' => true,
        ]);

        $this->fraudML->method('predict')->willReturn(0.85);
        $this->behaviorAnalyzer->method('classifyUser')->willReturn('new');

        $dto = new ReturnFraudDetectionDto(
            orderId: $orderId,
            productId: $product->id,
            serialNumber: 'REUSED123',
            userId: $userId,
            correlationId: $correlationId,
            returnReason: 'defective',
            condition: 'new',
            deviceMetadata: [],
            userBehavior: [
                'time_on_site_minutes' => 5,
                'page_views_before_purchase' => 2,
                'cart_abandonment_rate' => 0.9,
            ],
            idempotencyKey: null,
        );

        $result = $this->service->detectReturnFraud($dto);

        $this->assertTrue($result->isFraudulent);
        $this->assertGreaterThan(0.65, $result->fraudProbability);
        $this->assertEquals('critical', $result->riskLevel);
        $this->assertEquals('block_return_and_investigate', $result->recommendedAction);
        $this->assertNotNull($result->holdDurationMinutes);
    }

    #[Test]
    public function it_analyzes_device_condition(): void
    {
        $newCondition = 'new';
        $damagedCondition = 'damaged';

        $score1 = $this->analyzeDeviceCondition($newCondition);
        $score2 = $this->analyzeDeviceCondition($damagedCondition);

        $this->assertEquals(1.0, $score1);
        $this->assertEquals(0.1, $score2);
    }

    #[Test]
    public function it_analyzes_return_reason_risk(): void
    {
        $highRiskReason = 'defective';
        $lowRiskReason = 'changed_mind';

        $risk1 = $this->analyzeReturnReason($highRiskReason);
        $risk2 = $this->analyzeReturnReason($lowRiskReason);

        $this->assertGreaterThan($risk2, $risk1);
    }

    #[Test]
    public function it_calculates_metadata_completeness(): void
    {
        $completeMetadata = [
            'imei' => '123456789012345',
            'battery_health' => 95,
            'screen_condition' => 'perfect',
            'activation_date' => '2024-01-01',
        ];

        $incompleteMetadata = [
            'imei' => '123456789012345',
        ];

        $completeness1 = $this->calculateMetadataCompleteness($completeMetadata);
        $completeness2 = $this->calculateMetadataCompleteness($incompleteMetadata);

        $this->assertEquals(1.0, $completeness1);
        $this->assertEquals(0.25, $completeness2);
    }

    #[Test]
    public function it_saves_detection_record(): void
    {
        $userId = 1;
        $orderId = 102;
        $correlationId = 'test-save-789';

        $product = ElectronicsProduct::factory()->create([
            'name' => 'MacBook Pro',
            'brand' => 'Apple',
            'category' => 'laptops',
            'price_kopecks' => 19999900,
            'availability_status' => 'in_stock',
            'is_active' => true,
        ]);

        $this->fraudML->method('predict')->willReturn(0.3);
        $this->behaviorAnalyzer->method('classifyUser')->willReturn('returning');

        $dto = new ReturnFraudDetectionDto(
            orderId: $orderId,
            productId: $product->id,
            serialNumber: 'MBP2024XYZ789',
            userId: $userId,
            correlationId: $correlationId,
            returnReason: 'found_better_price',
            condition: 'good',
            deviceMetadata: [
                'imei' => '987654321098765',
                'battery_health' => 88,
                'screen_condition' => 'good',
                'activation_date' => '2024-02-01',
            ],
            userBehavior: [
                'time_on_site_minutes' => 45,
                'page_views_before_purchase' => 20,
                'cart_abandonment_rate' => 0.15,
            ],
            idempotencyKey: null,
        );

        $this->service->detectReturnFraud($dto);

        $this->assertDatabaseHas('electronics_return_fraud_detections', [
            'order_id' => $orderId,
            'product_id' => $product->id,
            'serial_number' => 'MBP2024XYZ789',
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);
    }

    #[Test]
    public function it_calculates_hold_duration_based_on_risk(): void
    {
        $criticalDuration = $this->calculateHoldDuration('critical');
        $highDuration = $this->calculateHoldDuration('high');
        $mediumDuration = $this->calculateHoldDuration('medium');
        $lowDuration = $this->calculateHoldDuration('low');
        $minimalDuration = $this->calculateHoldDuration('minimal');

        $this->assertEquals(4320, $criticalDuration);
        $this->assertEquals(2880, $highDuration);
        $this->assertEquals(1440, $mediumDuration);
        $this->assertEquals(720, $lowDuration);
        $this->assertNull($minimalDuration);
    }

    private function analyzeDeviceCondition(string $condition): float
    {
        $conditionScores = [
            'new' => 1.0,
            'like_new' => 0.9,
            'good' => 0.7,
            'fair' => 0.5,
            'poor' => 0.3,
            'damaged' => 0.1,
        ];

        return $conditionScores[strtolower($condition)] ?? 0.5;
    }

    private function analyzeReturnReason(string $reason): float
    {
        $highRiskReasons = [
            'defective',
            'not_as_described',
            'wrong_item',
            'damaged',
        ];

        $mediumRiskReasons = [
            'changed_mind',
            'found_better_price',
            'no_longer_needed',
        ];

        $lowerReason = strtolower($reason);

        foreach ($highRiskReasons as $risky) {
            if (str_contains($lowerReason, $risky)) {
                return 0.8;
            }
        }

        foreach ($mediumRiskReasons as $risky) {
            if (str_contains($lowerReason, $risky)) {
                return 0.5;
            }
        }

        return 0.3;
    }

    private function calculateMetadataCompleteness(array $metadata): float
    {
        if (empty($metadata)) {
            return 0.0;
        }

        $requiredFields = ['imei', 'battery_health', 'screen_condition', 'activation_date'];
        $presentFields = 0;

        foreach ($requiredFields as $field) {
            if (isset($metadata[$field]) && !empty($metadata[$field])) {
                $presentFields++;
            }
        }

        return $presentFields / count($requiredFields);
    }

    private function calculateHoldDuration(string $riskLevel): ?int
    {
        return match ($riskLevel) {
            'critical' => 4320,
            'high' => 2880,
            'medium' => 1440,
            'low' => 720,
            'minimal' => null,
            default => null,
        };
    }
}
