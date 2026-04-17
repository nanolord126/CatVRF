<?php declare(strict_types=1);

namespace Tests\Unit\Services\Fraud;

use App\Services\Fraud\MLInferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class MLInferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private MLInferenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MLInferenceService::class);
        
        // Reset circuit breaker before each test
        Redis::flushdb();
    }

    public function test_predict_returns_fallback_score_when_ml_unavailable(): void
    {
        Config::set('fraud.ml.onnx_enabled', false);
        Config::set('fraud.ml.http_endpoint', null);

        $features = [
            'transactions_5min' => 10,
            'amount' => 1_500_000,
            'device_changed_24h' => true,
            'ip_changed_24h' => true,
            'failed_attempts_1hour' => 6,
        ];

        $score = $this->service->predict($features);

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
        $this->assertGreaterThan(0.5, $score); // Should be high given the features
    }

    public function test_predict_uses_fallback_heuristics(): void
    {
        $features = [
            'transactions_5min' => 0,
            'amount' => 1000,
            'device_changed_24h' => false,
            'ip_changed_24h' => false,
            'failed_attempts_1hour' => 0,
        ];

        $score = $this->service->predict($features);

        $this->assertLessThan(0.5, $score); // Should be low for clean profile
    }

    public function test_predict_returns_high_score_for_suspicious_features(): void
    {
        $features = [
            'transactions_5min' => 10,
            'amount' => 2_000_000,
            'device_changed_24h' => true,
            'ip_changed_24h' => true,
            'failed_attempts_1hour' => 10,
        ];

        $score = $this->service->predict($features);

        $this->assertGreaterThan(0.8, $score);
    }

    public function test_circuit_breaker_opens_after_failures(): void
    {
        Config::set('fraud.ml.http_endpoint', 'http://invalid-endpoint');
        Config::set('fraud.ml.onnx_enabled', false);

        $features = ['transactions_5min' => 0];

        // Trigger failures
        for ($i = 0; $i < 6; $i++) {
            $this->service->predict($features);
        }

        $status = $this->service->getCircuitBreakerStatus();

        $this->assertTrue($status['is_open']);
        $this->assertGreaterThanOrEqual(5, $status['failures']);
    }

    public function test_circuit_breaker_can_be_reset(): void
    {
        Config::set('fraud.ml.http_endpoint', 'http://invalid-endpoint');
        
        $features = ['transactions_5min' => 0];

        // Trigger failures
        for ($i = 0; $i < 6; $i++) {
            $this->service->predict($features);
        }

        $this->assertTrue($this->service->getCircuitBreakerStatus()['is_open']);

        // Reset
        $this->service->resetCircuitBreaker();

        $this->assertFalse($this->service->getCircuitBreakerStatus()['is_open']);
    }

    public function test_predict_uses_http_endpoint_when_configured(): void
    {
        Http::fake([
            '*' => Http::response(['fraud_score' => 0.75], 200),
        ]);

        Config::set('fraud.ml.http_endpoint', 'http://test-ml-api/predict');
        Config::set('fraud.ml.onnx_enabled', false);

        $features = ['transactions_5min' => 0];
        $score = $this->service->predict($features);

        $this->assertEquals(0.75, $score);
        Http::assertSent(function ($request) {
            return $request->url() === 'http://test-ml-api/predict' &&
                   isset($request->data()['features']);
        });
    }

    public function test_predict_fallback_on_http_failure(): void
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        Config::set('fraud.ml.http_endpoint', 'http://test-ml-api/predict');
        Config::set('fraud.ml.onnx_enabled', false);

        $features = ['transactions_5min' => 0];
        $score = $this->service->predict($features);

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0);
    }

    public function test_get_circuit_breaker_status_returns_correct_data(): void
    {
        $status = $this->service->getCircuitBreakerStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('is_open', $status);
        $this->assertArrayHasKey('failures', $status);
        $this->assertArrayHasKey('threshold', $status);
        $this->assertArrayHasKey('ttl', $status);
        $this->assertEquals(5, $status['threshold']);
    }

    public function test_model_version_is_cached(): void
    {
        Cache::flush();

        $version1 = $this->service->getCurrentModelVersion();
        $version2 = $this->service->getCurrentModelVersion();

        $this->assertEquals($version1, $version2);
    }
}
