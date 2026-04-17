<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Commissions;

use App\Domains\Commissions\DTOs\CalculateCommissionDto;
use App\Domains\Commissions\Services\CommissionService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\LogManager;
use Tests\TestCase;

final class CommissionServiceTest extends TestCase
{
    private CommissionService $service;
    private DatabaseManager $db;
    private AuditService $audit;
    private FraudControlService $fraud;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = app(DatabaseManager::class);
        $this->audit = app(AuditService::class);
        $this->fraud = app(FraudControlService::class);
        
        $this->service = new CommissionService(
            $this->db,
            app(LogManager::class),
            $this->audit,
            $this->fraud,
        );
    }

    public function test_calculate_b2c_commission(): void
    {
        $dto = new CalculateCommissionDto(
            tenantId: 1,
            vertical: 'food',
            amount: 10000, // 100 рублей в копейках
            isB2B: false,
            monthlyVolume: null,
            correlationId: 'test-123',
        );

        $commission = $this->service->calculate($dto);

        // B2C default rate is 14%
        $this->assertEquals(1400, $commission); // 100 * 0.14 = 14 рублей = 1400 копеек
    }

    public function test_calculate_b2b_commission_default_rate(): void
    {
        $dto = new CalculateCommissionDto(
            tenantId: 1,
            vertical: 'food',
            amount: 10000,
            isB2B: true,
            monthlyVolume: null,
            correlationId: 'test-123',
        );

        $commission = $this->service->calculate($dto);

        // B2B default rate is 10%
        $this->assertEquals(1000, $commission);
    }

    public function test_calculate_auto_vertical_higher_rate(): void
    {
        $dto = new CalculateCommissionDto(
            tenantId: 1,
            vertical: 'auto',
            amount: 10000,
            isB2B: true,
            monthlyVolume: null,
            correlationId: 'test-123',
        );

        $commission = $this->service->calculate($dto);

        // Auto vertical has higher B2B rate (15%)
        $this->assertEquals(1500, $commission);
    }

    public function test_calculate_b2b_tiered_rate(): void
    {
        // Create commission rule with tiered rates
        $this->db->table('commission_rules')->insert([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'name' => 'Test Tiered Rule',
            'type' => 'tiered',
            'entity_type' => 'food',
            'b2c_rate' => 14.00,
            'b2b_rate' => 12.00,
            'b2b_tiers' => json_encode([
                ['min_volume' => 0, 'rate' => 12],
                ['min_volume' => 1000000, 'rate' => 10],
                ['min_volume' => 5000000, 'rate' => 8],
            ]),
            'is_active' => true,
            'correlation_id' => 'test-123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dto = new CalculateCommissionDto(
            tenantId: 1,
            vertical: 'food',
            amount: 10000,
            isB2B: true,
            monthlyVolume: 2000000, // Should use 10% rate
            correlationId: 'test-123',
        );

        $commission = $this->service->calculate($dto);

        // Tiered rate for 2M volume is 10%
        $this->assertEquals(1000, $commission);
    }

    public function test_record_commission(): void
    {
        $commissionId = $this->service->record(
            tenantId: 1,
            vertical: 'food',
            amount: 10000,
            commission: 1400,
            operationType: 'order',
            operationId: 1,
            correlationId: 'test-123',
            context: ['test' => true],
        );

        $this->assertIsInt($commissionId);
        $this->assertDatabaseHas('commission_records', [
            'id' => $commissionId,
            'tenant_id' => 1,
            'vertical' => 'food',
            'amount' => 10000,
            'commission' => 1400,
        ]);
    }

    public function test_record_commission_idempotent(): void
    {
        $correlationId = 'test-123';

        // First record
        $firstId = $this->service->record(
            tenantId: 1,
            vertical: 'food',
            amount: 10000,
            commission: 1400,
            operationType: 'order',
            operationId: 1,
            correlationId: $correlationId,
        );

        // Second record with same operation should return existing ID
        $secondId = $this->service->record(
            tenantId: 1,
            vertical: 'food',
            amount: 10000,
            commission: 1400,
            operationType: 'order',
            operationId: 1,
            correlationId: $correlationId,
        );

        $this->assertEquals($firstId, $secondId);
    }

    public function test_mark_as_paid(): void
    {
        $commissionId = $this->service->record(
            tenantId: 1,
            vertical: 'food',
            amount: 10000,
            commission: 1400,
            operationType: 'order',
            operationId: 2,
            correlationId: 'test-123',
        );

        $result = $this->service->markAsPaid($commissionId, 'test-123');

        $this->assertTrue($result);
        $this->assertDatabaseHas('commission_records', [
            'id' => $commissionId,
            'status' => 'paid',
        ]);
    }

    public function test_get_stats(): void
    {
        $this->service->record(
            tenantId: 1,
            vertical: 'food',
            amount: 10000,
            commission: 1400,
            operationType: 'order',
            operationId: 3,
            correlationId: 'test-123',
        );

        $stats = $this->service->getStats(1, 'food', 'month');

        $this->assertArrayHasKey('total_amount', $stats);
        $this->assertArrayHasKey('total_commission', $stats);
        $this->assertArrayHasKey('average_rate', $stats);
        $this->assertEquals(10000, $stats['total_amount']);
        $this->assertEquals(1400, $stats['total_commission']);
    }

    public function test_get_pending(): void
    {
        $commissionId = $this->service->record(
            tenantId: 1,
            vertical: 'food',
            amount: 10000,
            commission: 1400,
            operationType: 'order',
            operationId: 4,
            correlationId: 'test-123',
        );

        $pending = $this->service->getPending(1, 'food');

        $this->assertIsArray($pending);
        $this->assertGreaterThan(0, count($pending));
    }

    protected function tearDown(): void
    {
        $this->db->table('commission_records')->truncate();
        $this->db->table('commission_rules')->truncate();
        parent::tearDown();
    }
}
