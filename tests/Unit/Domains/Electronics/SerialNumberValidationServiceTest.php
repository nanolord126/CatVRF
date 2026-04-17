<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use App\Domains\Electronics\DTOs\SerialNumberValidationDto;
use App\Domains\Electronics\DTOs\FraudDetectionResultDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Services\SerialNumberValidationService;
use App\Services\FraudControlService;
use App\Services\FraudMLService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SerialNumberValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private SerialNumberValidationService $service;
    private FraudControlService $fraud;
    private FraudMLService $fraudML;
    private Cache $cache;
    private DatabaseManager $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->createMock(FraudControlService::class);
        $this->fraud->method('check')->willReturn(null);

        $this->fraudML = $this->createMock(FraudMLService::class);
        $this->cache = app(Cache::class);
        $this->db = app(DatabaseManager::class);

        $this->service = new SerialNumberValidationService(
            $this->fraud,
            $this->fraudML,
            $this->cache,
            $this->db,
            app('log'),
        );
    }

    #[Test]
    public function it_validates_serial_number_successfully(): void
    {
        $userId = 1;
        $correlationId = 'test-serial-123';

        $product = ElectronicsProduct::factory()->create([
            'name' => 'iPhone 15 Pro',
            'brand' => 'Apple',
            'category' => 'smartphones',
            'price_kopecks' => 9999900,
            'availability_status' => 'in_stock',
            'is_active' => true,
        ]);

        $this->fraudML->method('predict')->willReturn(0.15);

        $dto = new SerialNumberValidationDto(
            productId: $product->id,
            serialNumber: 'AP15PRO123456',
            userId: $userId,
            correlationId: $correlationId,
            orderId: null,
            purchaseDate: null,
            proofOfPurchaseUrl: null,
            idempotencyKey: null,
        );

        $result = $this->service->validateSerialNumber($dto);

        $this->assertInstanceOf(FraudDetectionResultDto::class, $result);
        $this->assertFalse($result->isFraudulent);
        $this->assertEquals($correlationId, $result->correlationId);
        $this->assertLessThan(0.7, $result->fraudProbability);
        $this->assertEquals('approve', $result->recommendedAction);
    }

    #[Test]
    public function it_detects_fraudulent_serial_number(): void
    {
        $userId = 1;
        $correlationId = 'test-serial-fraud-456';

        $product = ElectronicsProduct::factory()->create([
            'name' => 'Samsung Galaxy S24',
            'brand' => 'Samsung',
            'category' => 'smartphones',
            'price_kopecks' => 7999900,
            'availability_status' => 'in_stock',
            'is_active' => true,
        ]);

        $this->fraudML->method('predict')->willReturn(0.85);

        $dto = new SerialNumberValidationDto(
            productId: $product->id,
            serialNumber: 'INVALID123',
            userId: $userId,
            correlationId: $correlationId,
            orderId: null,
            purchaseDate: null,
            proofOfPurchaseUrl: null,
            idempotencyKey: null,
        );

        $result = $this->service->validateSerialNumber($dto);

        $this->assertTrue($result->isFraudulent);
        $this->assertGreaterThan(0.7, $result->fraudProbability);
        $this->assertEquals('critical', $result->riskLevel);
        $this->assertEquals('block_and_investigate', $result->recommendedAction);
        $this->assertEquals(1440, $result->holdDurationMinutes);
    }

    #[Test]
    public function it_analyzes_serial_pattern_correctly(): void
    {
        $validPattern = 'AP15PRO123456';
        $invalidPattern = 'abc123';

        $pattern1 = $this->service->analyzeSerialPattern($validPattern);
        $pattern2 = $this->service->analyzeSerialPattern($invalidPattern);

        $this->assertGreaterThan(0.7, $pattern1['score']);
        $this->assertLessThan(0.6, $pattern2['score']);
    }

    #[Test]
    public function it_calculates_entropy(): void
    {
        $lowEntropy = 'AAAAAAAA';
        $highEntropy = 'A1b2C3d4';

        $entropy1 = $this->service->calculateEntropy($lowEntropy);
        $entropy2 = $this->service->calculateEntropy($highEntropy);

        $this->assertLessThan($entropy2, $entropy1);
    }

    #[Test]
    public function it_saves_validation_record(): void
    {
        $userId = 1;
        $correlationId = 'test-save-789';

        $product = ElectronicsProduct::factory()->create([
            'name' => 'MacBook Pro',
            'brand' => 'Apple',
            'category' => 'laptops',
            'price_kopecks' => 19999900,
            'availability_status' => 'in_stock',
            'is_active' => true,
        ]);

        $this->fraudML->method('predict')->willReturn(0.25);

        $dto = new SerialNumberValidationDto(
            productId: $product->id,
            serialNumber: 'MBP2024XYZ789',
            userId: $userId,
            correlationId: $correlationId,
            orderId: null,
            purchaseDate: null,
            proofOfPurchaseUrl: null,
            idempotencyKey: null,
        );

        $this->service->validateSerialNumber($dto);

        $this->assertDatabaseHas('electronics_serial_validations', [
            'product_id' => $product->id,
            'serial_number' => 'MBP2024XYZ789',
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);
    }

    #[Test]
    public function it_returns_cached_result(): void
    {
        $userId = 1;
        $correlationId = 'test-cache-101';

        $product = ElectronicsProduct::factory()->create([
            'name' => 'iPad Pro',
            'brand' => 'Apple',
            'category' => 'tablets',
            'price_kopecks' => 8999900,
            'availability_status' => 'in_stock',
            'is_active' => true,
        ]);

        $this->fraudML->expects($this->once())->method('predict')->willReturn(0.2);

        $dto = new SerialNumberValidationDto(
            productId: $product->id,
            serialNumber: 'IPAD2024TEST',
            userId: $userId,
            correlationId: $correlationId,
            orderId: null,
            purchaseDate: null,
            proofOfPurchaseUrl: null,
            idempotencyKey: null,
        );

        $firstResult = $this->service->validateSerialNumber($dto);
        $secondResult = $this->service->validateSerialNumber($dto);

        $this->assertEquals($firstResult->fraudProbability, $secondResult->fraudProbability);
    }

    private function analyzeSerialPattern(string $serialNumber): array
    {
        $patterns = [
            '/^[A-Z]{2}[0-9]{6}$/' => 0.95,
            '/^[0-9]{10}$/' => 0.90,
            '/^[A-Z0-9]{12}$/' => 0.85,
            '/^[A-Z]{3}-[0-9]{4}-[A-Z]{2}$/' => 0.80,
        ];

        foreach ($patterns as $pattern => $score) {
            if (preg_match($pattern, $serialNumber)) {
                return ['pattern' => $pattern, 'score' => $score];
            }
        }

        return ['pattern' => 'unknown', 'score' => 0.5];
    }

    private function calculateEntropy(string $string): float
    {
        $entropy = 0.0;
        $length = strlen($string);

        if ($length === 0) {
            return 0.0;
        }

        $frequency = array_count_values(str_split($string));

        foreach ($frequency as $count) {
            $probability = $count / $length;
            $entropy -= $probability * log($probability, 2);
        }

        return $entropy;
    }
}
