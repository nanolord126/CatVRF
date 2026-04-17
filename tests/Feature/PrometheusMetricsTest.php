<?php declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\FraudML\Services\PrometheusMetricsService;
use Illuminate\Support\Facades\Config;
use Tests\BaseTestCase;

/**
 * Prometheus Metrics Integration Test
 * 
 * Tests the Prometheus metrics integration for CatVRF ML pipeline.
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class PrometheusMetricsTest extends BaseTestCase
{
    public function test_prometheus_metrics_service_can_be_instantiated(): void
    {
        $service = app(PrometheusMetricsService::class);
        
        $this->assertInstanceOf(PrometheusMetricsService::class, $service);
    }

    public function test_prometheus_configuration_has_required_settings(): void
    {
        $this->assertNotNull(Config::get('prometheus.storage_driver'));
        $this->assertNotNull(Config::get('prometheus.namespace'));
        $this->assertNotNull(Config::get('prometheus.route.enabled'));
    }

    public function test_prometheus_namespace_is_configured(): void
    {
        $namespace = Config::get('prometheus.namespace');
        
        $this->assertEquals('catvrf', $namespace);
    }

    public function test_prometheus_storage_driver_is_redis(): void
    {
        $storageDriver = Config::get('prometheus.storage_driver');
        
        $this->assertEquals('redis', $storageDriver);
    }

    public function test_prometheus_metrics_route_is_enabled(): void
    {
        $routeEnabled = Config::get('prometheus.route.enabled');
        
        $this->assertTrue($routeEnabled);
    }

    public function test_prometheus_metrics_service_can_record_retrain_duration(): void
    {
        $service = app(PrometheusMetricsService::class);
        $correlationId = 'test-' . uniqid();
        
        $this->expectNotToPerformAssertions();
        
        $service->recordRetrainDuration(1.5, $correlationId);
    }

    public function test_prometheus_metrics_service_can_record_retrain_success(): void
    {
        $service = app(PrometheusMetricsService::class);
        $correlationId = 'test-' . uniqid();
        
        $this->expectNotToPerformAssertions();
        
        $service->recordRetrainSuccess('completed', $correlationId);
    }

    public function test_prometheus_metrics_service_can_record_model_auc(): void
    {
        $service = app(PrometheusMetricsService::class);
        $correlationId = 'test-' . uniqid();
        
        $this->expectNotToPerformAssertions();
        
        $service->recordModelAUC(0.95, 'v1.0.0', $correlationId);
    }

    public function test_prometheus_metrics_service_can_record_feature_drift_psi(): void
    {
        $service = app(PrometheusMetricsService::class);
        $correlationId = 'test-' . uniqid();
        
        $this->expectNotToPerformAssertions();
        
        $service->recordFeatureDriftPSI(0.15, 'amount_log', 'medical', $correlationId);
    }

    public function test_prometheus_metrics_service_can_record_quota_usage_ratio(): void
    {
        $service = app(PrometheusMetricsService::class);
        $correlationId = 'test-' . uniqid();
        
        $this->expectNotToPerformAssertions();
        
        $service->recordQuotaUsageRatio(0.75, 'ai_tokens', 'medical', $correlationId);
    }

    public function test_prometheus_metrics_service_can_record_quota_exceeded(): void
    {
        $service = app(PrometheusMetricsService::class);
        $correlationId = 'test-' . uniqid();
        
        $this->expectNotToPerformAssertions();
        
        $service->recordQuotaExceeded('ai_tokens', 'medical', $correlationId);
    }

    public function test_prometheus_metrics_service_can_record_ai_tokens_consumed(): void
    {
        $service = app(PrometheusMetricsService::class);
        $correlationId = 'test-' . uniqid();
        
        $this->expectNotToPerformAssertions();
        
        $service->recordAITokensConsumed(1000, 'gpt-4', 'medical', $correlationId);
    }

    public function test_prometheus_metrics_service_can_record_fraud_ml_inference_latency(): void
    {
        $service = app(PrometheusMetricsService::class);
        $correlationId = 'test-' . uniqid();
        
        $this->expectNotToPerformAssertions();
        
        $service->recordFraudMLInferenceLatency(0.05, 'v1.0.0', $correlationId);
    }

    public function test_prometheus_metrics_service_can_record_fraud_score(): void
    {
        $service = app(PrometheusMetricsService::class);
        $correlationId = 'test-' . uniqid();
        
        $this->expectNotToPerformAssertions();
        
        $service->recordFraudScore(0.3, 'medical', $correlationId);
    }

    public function test_prometheus_metrics_service_can_record_fraud_blocked(): void
    {
        $service = app(PrometheusMetricsService::class);
        $correlationId = 'test-' . uniqid();
        
        $this->expectNotToPerformAssertions();
        
        $service->recordFraudBlocked('high_score', 'medical', $correlationId);
    }
}
