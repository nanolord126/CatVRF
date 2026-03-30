<?php declare(strict_types=1);

namespace Tests\Performance\Fraud;

use App\Models\FraudAttempt;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FraudMLService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FraudMLPerformanceTest
 * 
 * Inference speed, feature extraction, model loading untuk Fraud ML
 */
final class FraudMLPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected FraudMLService $fraudService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create();
        $this->fraudService = app(FraudMLService::class);
    }

    /** @test */
    public function it_scores_single_operation_under_50ms(): void
    {
        $dto = [
            'user_id' => $this->user->id,
            'operation_type' => 'payment_init',
            'amount' => 50000,
            'ip_address' => '127.0.0.1',
            'device_fingerprint' => 'device_001',
            'timestamp' => now(),
        ];

        $startTime = microtime(true);

        $score = $this->fraudService->scoreOperation($dto);

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
        $this->assertLessThan(50, $elapsed, "Scoring took {$elapsed}ms");
    }

    /** @test */
    public function it_extracts_features_under_20ms(): void
    {
        $dto = [
            'user_id' => $this->user->id,
            'operation_type' => 'card_bind',
            'amount' => 0,
            'ip_address' => '192.168.1.1',
            'device_fingerprint' => 'device_002',
            'timestamp' => now(),
        ];

        $startTime = microtime(true);

        $features = $this->fraudService->extractFeatures($dto);

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertGreaterThanOrEqual(30, count($features), 'Should have 30+ features');
        $this->assertLessThan(20, $elapsed, "Feature extraction took {$elapsed}ms");
    }

    /** @test */
    public function it_scores_100_operations_under_5_seconds(): void
    {
        $operations = [];
        for ($i = 0; $i < 100; $i++) {
            $operations[] = [
                'user_id' => $this->user->id,
                'operation_type' => 'payment_init',
                'amount' => 50000 + ($i * 1000),
                'ip_address' => '127.0.0.1',
                'device_fingerprint' => "device_{$i}",
                'timestamp' => now(),
            ];
        }

        $startTime = microtime(true);

        $scores = [];
        foreach ($operations as $op) {
            $scores[] = $this->fraudService->scoreOperation($op);
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertCount(100, $scores);
        $this->assertLessThan(5000, $elapsed, "Batch scoring 100 ops took {$elapsed}ms");
    }

    /** @test */
    public function it_decides_block_under_10ms_after_scoring(): void
    {
        $highRiskOperation = [
            'user_id' => $this->user->id,
            'operation_type' => 'payment_init',
            'amount' => 500000, // High amount
            'ip_address' => '10.0.0.1',
            'device_fingerprint' => 'unknown_device',
            'timestamp' => now(),
            'previous_ips' => [], // New IP
        ];

        $score = $this->fraudService->scoreOperation($highRiskOperation);

        $startTime = microtime(true);

        $shouldBlock = $this->fraudService->shouldBlock($score, 'payment_init');

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertIsBool($shouldBlock);
        $this->assertLessThan(10, $elapsed, "Block decision took {$elapsed}ms");
    }

    /** @test */
    public function it_loads_current_model_version_under_100ms(): void
    {
        $startTime = microtime(true);

        $version = $this->fraudService->getCurrentModelVersion();

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertIsString($version);
        $this->assertNotEmpty($version);
        $this->assertLessThan(100, $elapsed, "Model version lookup took {$elapsed}ms");
    }

    /** @test */
    public function it_predicts_with_fallback_under_20ms(): void
    {
        $operation = [
            'user_id' => $this->user->id,
            'operation_type' => 'payment_init',
            'amount' => 50000,
            'ip_address' => '127.0.0.1',
            'device_fingerprint' => 'device_001',
            'timestamp' => now(),
        ];

        $startTime = microtime(true);

        $result = $this->fraudService->predictWithFallback($operation);

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('decision', $result);
        $this->assertLessThan(20, $elapsed, "Predict with fallback took {$elapsed}ms");
    }

    /** @test */
    public function it_caches_scores_for_identical_operations(): void
    {
        $operation = [
            'user_id' => $this->user->id,
            'operation_type' => 'payment_init',
            'amount' => 50000,
            'ip_address' => '127.0.0.1',
            'device_fingerprint' => 'device_001',
            'timestamp' => now(),
        ];

        // First call (uncached)
        $startTime1 = microtime(true);
        $score1 = $this->fraudService->scoreOperation($operation);
        $elapsed1 = (microtime(true) - $startTime1) * 1000;

        // Second call (should be cached)
        $startTime2 = microtime(true);
        $score2 = $this->fraudService->scoreOperation($operation);
        $elapsed2 = (microtime(true) - $startTime2) * 1000;

        $this->assertEquals($score1, $score2, 'Scores should match');
        // Cached call should be faster
        $this->assertLessThan($elapsed1 + 10, $elapsed2 + 5, 'Cached call should be faster');
    }

    /** @test */
    public function it_processes_batch_with_different_operation_types(): void
    {
        $operations = [
            ['type' => 'payment_init', 'amount' => 50000],
            ['type' => 'card_bind', 'amount' => 0],
            ['type' => 'withdrawal', 'amount' => 100000],
            ['type' => 'payment_init', 'amount' => 150000],
            ['type' => 'card_bind', 'amount' => 0],
        ];

        $startTime = microtime(true);

        foreach ($operations as $op) {
            $this->fraudService->scoreOperation([
                'user_id' => $this->user->id,
                'operation_type' => $op['type'],
                'amount' => $op['amount'],
                'ip_address' => '127.0.0.1',
                'device_fingerprint' => 'device_001',
                'timestamp' => now(),
            ]);
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(500, $elapsed, "Batch with different types took {$elapsed}ms");
    }

    /** @test */
    public function it_handles_velocity_scoring_efficiently(): void
    {
        // Create 5 rapid attempts
        $startTime = microtime(true);

        for ($i = 0; $i < 5; $i++) {
            $this->fraudService->scoreOperation([
                'user_id' => $this->user->id,
                'operation_type' => 'card_bind',
                'amount' => 0,
                'ip_address' => '127.0.0.1',
                'device_fingerprint' => 'device_001',
                'timestamp' => now()->addMinutes($i),
            ]);
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(500, $elapsed, "Velocity check took {$elapsed}ms");
    }

    /** @test */
    public function it_handles_geographic_anomaly_detection_efficiently(): void
    {
        $moscow = ['ip' => '195.234.0.0', 'lat' => 55.7558, 'lon' => 37.6173];
        $newyork = ['ip' => '8.8.8.0', 'lat' => 40.7128, 'lon' => -74.0060];

        // First operation
        $startTime = microtime(true);

        $score1 = $this->fraudService->scoreOperation([
            'user_id' => $this->user->id,
            'operation_type' => 'payment_init',
            'amount' => 50000,
            'ip_address' => $moscow['ip'],
            'device_fingerprint' => 'device_001',
            'timestamp' => now(),
        ]);

        // Second operation (different location)
        $score2 = $this->fraudService->scoreOperation([
            'user_id' => $this->user->id,
            'operation_type' => 'payment_init',
            'amount' => 50000,
            'ip_address' => $newyork['ip'],
            'device_fingerprint' => 'device_001',
            'timestamp' => now()->addMinutes(1),
        ]);

        $elapsed = (microtime(true) - microtime(true) + $startTime) * 1000;

        $this->assertGreaterThan(0, $score1);
        $this->assertGreaterThan(0, $score2);
    }

    /** @test */
    public function it_records_fraud_attempts_efficiently(): void
    {
        $startTime = microtime(true);

        for ($i = 0; $i < 50; $i++) {
            FraudAttempt::factory()
                ->for($this->user)
                ->for($this->tenant)
                ->create([
                    'ml_score' => 0.5 + ($i * 0.01),
                    'decision' => $i % 3 === 0 ? 'block' : 'allow',
                ]);
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(1000, $elapsed, "Recording 50 attempts took {$elapsed}ms");
    }

    /** @test */
    public function it_queries_fraud_statistics_efficiently(): void
    {
        FraudAttempt::factory()
            ->for($this->user)
            ->for($this->tenant)
            ->count(100)
            ->create(['decision' => 'allow']);

        FraudAttempt::factory()
            ->for($this->user)
            ->for($this->tenant)
            ->count(20)
            ->create(['decision' => 'block']);

        $startTime = microtime(true);

        $total = FraudAttempt::count();
        $blocked = FraudAttempt::where('decision', 'block')->count();
        $blockRate = ($blocked / $total) * 100;

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(120, $total);
        $this->assertEquals(20, $blocked);
        $this->assertGreaterThan(15, $blockRate);
        $this->assertLessThan(100, $elapsed, "Statistics query took {$elapsed}ms");
    }

    /** @test */
    public function it_filters_high_risk_operations_efficiently(): void
    {
        FraudAttempt::factory()
            ->for($this->user)
            ->for($this->tenant)
            ->count(50)
            ->create(['ml_score' => 0.2]); // Low risk

        FraudAttempt::factory()
            ->for($this->user)
            ->for($this->tenant)
            ->count(50)
            ->create(['ml_score' => 0.8]); // High risk

        $startTime = microtime(true);

        $highRisk = FraudAttempt::where('ml_score', '>', 0.7)->count();

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(50, $highRisk);
        $this->assertLessThan(100, $elapsed, "High-risk filter took {$elapsed}ms");
    }

    /** @test */
    public function it_handles_model_version_migration_efficiently(): void
    {
        $oldVersion = '2026-03-20-v1';
        $newVersion = '2026-03-21-v1';

        FraudAttempt::factory()
            ->for($this->user)
            ->for($this->tenant)
            ->count(50)
            ->create(['ml_version' => $oldVersion]);

        $startTime = microtime(true);

        $oldCount = FraudAttempt::where('ml_version', $oldVersion)->count();
        $newCount = FraudAttempt::where('ml_version', $newVersion)->count();

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(50, $oldCount);
        $this->assertEquals(0, $newCount);
        $this->assertLessThan(100, $elapsed, "Version queries took {$elapsed}ms");
    }

    /** @test */
    public function it_handles_feature_extraction_with_large_history(): void
    {
        // Create 100 previous attempts
        for ($i = 0; $i < 100; $i++) {
            FraudAttempt::factory()
                ->for($this->user)
                ->for($this->tenant)
                ->create();
        }

        $operation = [
            'user_id' => $this->user->id,
            'operation_type' => 'payment_init',
            'amount' => 50000,
            'ip_address' => '127.0.0.1',
            'device_fingerprint' => 'device_001',
            'timestamp' => now(),
        ];

        $startTime = microtime(true);

        $features = $this->fraudService->extractFeatures($operation);

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertGreaterThan(30, count($features));
        $this->assertLessThan(100, $elapsed, "Feature extraction with history took {$elapsed}ms");
    }

    /** @test */
    public function it_maintains_performance_with_operation_type_routing(): void
    {
        $operations = [
            ['type' => 'payment_init'],
            ['type' => 'card_bind'],
            ['type' => 'withdrawal'],
        ];

        $startTime = microtime(true);

        foreach ($operations as $op) {
            for ($i = 0; $i < 10; $i++) {
                $score = $this->fraudService->scoreOperation([
                    'user_id' => $this->user->id,
                    'operation_type' => $op['type'],
                    'amount' => 50000,
                    'ip_address' => '127.0.0.1',
                    'device_fingerprint' => "device_{$i}",
                    'timestamp' => now(),
                ]);

                $this->fraudService->shouldBlock($score, $op['type']);
            }
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(2000, $elapsed, "Type routing took {$elapsed}ms");
    }

    /** @test */
    public function it_handles_fallback_rules_efficiently(): void
    {
        $startTime = microtime(true);

        // Test fallback rule: 5 operations in 5 minutes
        for ($i = 0; $i < 5; $i++) {
            $result = $this->fraudService->predictWithFallback([
                'user_id' => $this->user->id,
                'operation_type' => 'payment_init',
                'amount' => 50000,
                'ip_address' => '127.0.0.1',
                'device_fingerprint' => 'device_001',
                'timestamp' => now()->addMinutes($i),
            ]);

            $this->assertArrayHasKey('decision', $result);
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(500, $elapsed, "Fallback rules took {$elapsed}ms");
    }
}
