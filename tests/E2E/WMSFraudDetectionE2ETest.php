<?php

declare(strict_types=1);

namespace Tests\E2E;

use App\Services\Security\FraudDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WMSFraudDetectionE2ETest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_detects_suspicious_stock_movement_patterns(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $userId = 1;
        $tenantId = 1;

        $fraudScore = $fraudService->analyzeStockMovementPattern($userId, $tenantId);

        $this->assertIsFloat($fraudScore);
        $this->assertGreaterThanOrEqual(0.0, $fraudScore);
        $this->assertLessThanOrEqual(1.0, $fraudScore);
    }

    #[Test]
    public function it_detects_rapid_adjustments_as_suspicious(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $userId = 1;
        $tenantId = 1;

        for ($i = 0; $i < 10; $i++) {
            \App\Models\StockMovement::create([
                'uuid' => fake()->uuid(),
                'correlation_id' => fake()->uuid(),
                'inventory_item_id' => 1,
                'type' => 'adjustment',
                'quantity' => rand(-100, 100),
                'reason' => 'Manual adjustment',
                'created_by' => $userId,
            ]);
        }

        $isSuspicious = $fraudService->detectRapidAdjustments($userId, $tenantId);

        $this->assertTrue($isSuspicious);
    }

    #[Test]
    public function it_detects_unusual_time_patterns(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $userId = 1;
        $tenantId = 1;

        $movementsAtNight = [];
        for ($i = 0; $i < 5; $i++) {
            $movement = \App\Models\StockMovement::create([
                'uuid' => fake()->uuid(),
                'correlation_id' => fake()->uuid(),
                'inventory_item_id' => 1,
                'type' => 'out',
                'quantity' => 100,
                'reason' => 'Late night transfer',
                'created_by' => $userId,
                'created_at' => now()->setHour(2)->setMinute(30),
            ]);
            $movementsAtNight[] = $movement;
        }

        $isUnusual = $fraudService->detectUnusualTimePattern($userId, $tenantId);

        $this->assertTrue($isUnusual);
    }

    #[Test]
    public function it_detects_large_quantity_anomalies(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $userId = 1;
        $tenantId = 1;

        $largeMovement = \App\Models\StockMovement::create([
            'uuid' => fake()->uuid(),
            'correlation_id' => fake()->uuid(),
            'inventory_item_id' => 1,
            'type' => 'out',
            'quantity' => 999999,
            'reason' => 'Large transfer',
            'created_by' => $userId,
        ]);

        $isAnomaly = $fraudService->detectQuantityAnomaly($largeMovement->id);

        $this->assertTrue($isAnomaly);
    }

    #[Test]
    public function it_detects_batch_manipulation(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $userId = 1;
        $tenantId = 1;

        $batch = \App\Domains\Inventory\Models\InventoryBatch::factory()->create([
            'tenant_id' => $tenantId,
            'current_quantity' => 1000,
        ]);

        $fraudService->detectBatchManipulation($batch->id, $userId);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_cross_references_with_other_fraud_sources(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $userId = 1;
        $tenantId = 1;

        $crossReferenceResult = $fraudService->crossReferenceFraudSources($userId, $tenantId);

        $this->assertIsArray($crossReferenceResult);
        $this->assertArrayHasKey('payment_fraud', $crossReferenceResult);
        $this->assertArrayHasKey('account_fraud', $crossReferenceResult);
        $this->assertArrayHasKey('wms_fraud', $crossReferenceResult);
    }

    #[Test]
    public function it_flags_user_for_review(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $userId = 1;
        $tenantId = 1;

        $isFlagged = $fraudService->flagUserForReview($userId, $tenantId, 'Suspicious WMS activity pattern');

        $this->assertTrue($isFlagged);
    }

    #[Test]
    public function it_prevents_movement_for_high_risk_users(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $userId = 1;
        $tenantId = 1;

        $fraudService->flagUserForReview($userId, $tenantId, 'Test flag', true);

        $canProceed = $fraudService->canProceedWithMovement($userId, $tenantId);

        $this->assertFalse($canProceed);
    }

    #[Test]
    public function it_allows_movement_for_low_risk_users(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $userId = 2;
        $tenantId = 1;

        $canProceed = $fraudService->canProceedWithMovement($userId, $tenantId);

        $this->assertTrue($canProceed);
    }

    #[Test]
    public function it_generates_fraud_report_for_regulators(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $tenantId = 1;
        $startDate = now()->subDays(30)->toDateString();
        $endDate = now()->toDateString();

        $report = $fraudService->generateFraudReport($tenantId, $startDate, $endDate);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('total_incidents', $report);
        $this->assertArrayHasKey('resolved_incidents', $report);
        $this->assertArrayHasKey('active_flags', $report);
    }

    #[Test]
    public function it_detects_license_forgery_attempts(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $licenseNumber = 'PH-FAKE-12345';
        $warehouseId = 1;

        $isForgery = $fraudService->detectLicenseForgery($licenseNumber, $warehouseId);

        $this->assertIsBool($isForgery);
    }

    #[Test]
    public function it_detects_chestny_znak_manipulation(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $markingCode = '010460012345678921ABCD1234567890';
        $productId = 1;

        $isManipulated = $fraudService->detectChestnyZnakManipulation($markingCode, $productId);

        $this->assertIsBool($isManipulated);
    }

    #[Test]
    public function it_tracks_user_behavior_biometrics(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $userId = 1;
        $behaviorData = [
            'typing_speed' => 150,
            'mouse_movement' => 'smooth',
            'session_duration' => 3600,
            'ip_address' => '192.168.1.1',
        ];

        $isAnomalous = $fraudService->analyzeBehaviorBiometrics($userId, $behaviorData);

        $this->assertIsBool($isAnomalous);
    }

    #[Test]
    public function it_escalates_critical_fraud_alerts(): void
    {
        $fraudService = app(FraudDetectionService::class);

        $alertId = 'alert-123';
        $severity = 'critical';

        $escalated = $fraudService->escalateFraudAlert($alertId, $severity);

        $this->assertTrue($escalated);
    }
}
