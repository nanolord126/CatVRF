<?php declare(strict_types=1);

namespace Tests\Unit\Services\Fraud;

use App\Services\Fraud\FraudTelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class FraudTelemetryServiceTest extends TestCase
{
    use RefreshDatabase;

    private FraudTelemetryService $telemetry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->telemetry = app(FraudTelemetryService::class);
        Redis::flushdb();
    }

    public function test_record_check_increments_counters(): void
    {
        $this->telemetry->recordCheck('payment_init', 0.3, 'allow', 50.5);

        $metrics = $this->telemetry->getPrometheusMetrics();

        $this->assertStringContainsString('fraud_checks_total 1', $metrics);
        $this->assertStringContainsString('fraud_checks_operation_payment_init 1', $metrics);
        $this->assertStringContainsString('fraud_checks_decision_allow 1', $metrics);
    }

    public function test_record_check_tracks_different_decisions(): void
    {
        $this->telemetry->recordCheck('payment_init', 0.9, 'block', 100.0);
        $this->telemetry->recordCheck('payment_init', 0.7, 'review', 75.0);
        $this->telemetry->recordCheck('payment_init', 0.2, 'allow', 50.0);

        $metrics = $this->telemetry->getPrometheusMetrics();

        $this->assertStringContainsString('fraud_checks_decision_block 1', $metrics);
        $this->assertStringContainsString('fraud_checks_decision_review 1', $metrics);
        $this->assertStringContainsString('fraud_checks_decision_allow 1', $metrics);
    }

    public function test_record_check_increments_latency(): void
    {
        $this->telemetry->recordCheck('payment_init', 0.3, 'allow', 100.0);
        $this->telemetry->recordCheck('payment_init', 0.3, 'allow', 200.0);

        $metrics = $this->telemetry->getPrometheusMetrics();

        $this->assertStringContainsString('fraud_check_latency_ms_avg', $metrics);
    }

    public function test_record_ml_inference_tracks_success(): void
    {
        $this->telemetry->recordMLInference(
            success: true,
            latencyMs: 50.0,
            circuitOpen: false,
            modelVersion: 'v1.0.0',
        );

        $metrics = $this->telemetry->getPrometheusMetrics();

        $this->assertStringContainsString('fraud_ml_inference_total 1', $metrics);
        $this->assertStringContainsString('fraud_ml_inference_success 1', $metrics);
    }

    public function test_record_ml_inference_tracks_failure(): void
    {
        $this->telemetry->recordMLInference(
            success: false,
            latencyMs: 100.0,
            circuitOpen: true,
        );

        $metrics = $this->telemetry->getPrometheusMetrics();

        $this->assertStringContainsString('fraud_ml_inference_total 1', $metrics);
        $this->assertStringContainsString('fraud_ml_inference_failure 1', $metrics);
        $this->assertStringContainsString('fraud_ml_circuit_open 1', $metrics);
    }

    public function test_record_atomic_lock_tracks_operations(): void
    {
        $this->telemetry->recordAtomicLock('slot_hold', true, 'success');
        $this->telemetry->recordAtomicLock('slot_hold', false, 'slot_already_held');

        $counters = Redis::keys('fraud_atomic_lock_slot_hold_*');
        
        $this->assertNotEmpty($counters);
    }

    public function test_get_statistics_returns_correct_data(): void
    {
        $this->telemetry->recordCheck('payment_init', 0.9, 'block', 100.0);
        $this->telemetry->recordCheck('payment_init', 0.3, 'allow', 50.0);
        $this->telemetry->recordCheck('payment_init', 0.3, 'allow', 50.0);

        $stats = $this->telemetry->getStatistics();

        $this->assertEquals(3, $stats['total_checks']);
        $this->assertEquals(1, $stats['blocked']);
        $this->assertEquals(2, $stats['allowed']);
        $this->assertEquals(33.33, round($stats['block_rate'], 2));
        $this->assertIsArray($stats['hourly_trends']);
    }

    public function test_get_statistics_includes_ml_metrics(): void
    {
        $this->telemetry->recordMLInference(true, 50.0, false, 'v1.0.0');
        $this->telemetry->recordMLInference(true, 60.0, false, 'v1.0.0');
        $this->telemetry->recordMLInference(false, 100.0, true);

        $stats = $this->telemetry->getStatistics();

        $this->assertEquals(3, $stats['ml_success_rate']); // 2/3 * 100 = 66.67, rounded
        $this->assertGreaterThan(0, $stats['ml_avg_latency_ms']);
    }

    public function test_get_prometheus_metrics_returns_valid_format(): void
    {
        $this->telemetry->recordCheck('payment_init', 0.3, 'allow', 50.0);

        $metrics = $this->telemetry->getPrometheusMetrics();

        $this->assertStringContainsString('# HELP', $metrics);
        $this->assertStringContainsString('# TYPE', $metrics);
        $this->assertStringContainsString('fraud_', $metrics);
    }

    public function test_score_histogram_buckets(): void
    {
        $this->telemetry->recordCheck('payment_init', 0.15, 'allow', 50.0);
        $this->telemetry->recordCheck('payment_init', 0.85, 'block', 50.0);

        $metrics = $this->telemetry->getPrometheusMetrics();

        $this->assertStringContainsString('fraud_score_bucket', $metrics);
        $this->assertStringContainsString('le="0.1"', $metrics);
        $this->assertStringContainsString('le="1.0"', $metrics);
    }

    public function test_reset_metrics_clears_all(): void
    {
        $this->telemetry->recordCheck('payment_init', 0.3, 'allow', 50.0);
        $this->telemetry->recordMLInference(true, 50.0, false);

        $this->telemetry->resetMetrics();

        $metrics = $this->telemetry->getPrometheusMetrics();

        $this->assertStringContainsString('fraud_checks_total 0', $metrics);
        $this->assertStringContainsString('fraud_ml_inference_total 0', $metrics);
    }

    public function test_hourly_trends_includes_multiple_hours(): void
    {
        $this->telemetry->recordCheck('payment_init', 0.3, 'allow', 50.0);

        $stats = $this->telemetry->getStatistics(24);

        $this->assertCount(24, $stats['hourly_trends']);
        
        foreach ($stats['hourly_trends'] as $trend) {
            $this->assertArrayHasKey('hour', $trend);
            $this->assertArrayHasKey('allow', $trend);
            $this->assertArrayHasKey('review', $trend);
            $this->assertArrayHasKey('block', $trend);
        }
    }

    public function test_circuit_breaker_status_in_statistics(): void
    {
        $stats = $this->telemetry->getStatistics();

        $this->assertArrayHasKey('circuit_breaker_open', $stats);
        $this->assertIsBool($stats['circuit_breaker_open']);
    }

    public function test_prometheus_metrics_format_is_parsable(): void
    {
        $this->telemetry->recordCheck('payment_init', 0.3, 'allow', 50.0);

        $metrics = $this->telemetry->getPrometheusMetrics();
        $lines = explode("\n", trim($metrics));

        foreach ($lines as $line) {
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            
            $parts = explode(' ', $line);
            $this->assertGreaterThanOrEqual(2, count($parts));
            $this->assertIsNumeric($parts[count($parts) - 1]);
        }
    }
}
