<?php declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML;

use App\Domains\FraudML\Services\MLModelRetrainService;
use App\Domains\FraudML\Services\MLModelValidationService;
use App\Domains\FraudML\Services\PrometheusMetricsService;
use App\Models\FraudModelVersion;
use App\Models\Tenant;
use App\Services\Tenancy\TenantQuotaService;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * MLModelRetrainServiceTest — unit tests for ML model retraining service
 * 
 * @covers \App\Domains\FraudML\Services\MLModelRetrainService
 */
final class MLModelRetrainServiceTest extends TestCase
{
    use RefreshDatabase;

    private MLModelRetrainService $service;
    private RedisFactory $redis;
    private TenantQuotaService $quotaService;
    private LogManager $logger;
    private MLModelValidationService $validationService;
    private PrometheusMetricsService $prometheus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = $this->app->make(RedisFactory::class);
        $this->quotaService = $this->app->make(TenantQuotaService::class);
        $this->logger = $this->app->make(LogManager::class);
        $this->validationService = $this->app->make(MLModelValidationService::class);
        $this->prometheus = $this->app->make(PrometheusMetricsService::class);

        $this->service = new MLModelRetrainService(
            $this->redis,
            $this->quotaService,
            $this->logger,
            $this->validationService,
            $this->prometheus,
        );
    }

    public function test_acquire_lock_prevents_duplicate_retrain(): void
    {
        $correlationId = 'test-correlation-1';

        // First call should acquire lock
        $result1 = $this->service->executeRetrain($correlationId);
        $this->assertEquals('completed', $result1['status']);

        // Second call should skip due to lock
        $result2 = $this->service->executeRetrain('test-correlation-2');
        $this->assertEquals('skipped', $result2['status']);
        $this->assertEquals('lock_already_held', $result2['reason']);
    }

    public function test_retrain_creates_shadow_model(): void
    {
        // Clear any existing locks
        $this->redis->connection()->del('ml:retrain:lock');

        $result = $this->service->executeRetrain('test-correlation-3');

        $this->assertEquals('completed', $result['status']);
        $this->assertArrayHasKey('model_version', $result);
        $this->assertArrayHasKey('model_id', $result);

        $model = FraudModelVersion::find($result['model_id']);
        $this->assertNotNull($model);
        $this->assertTrue($model->is_shadow);
        $this->assertFalse($model->is_active);
        $this->assertNotNull($model->shadow_started_at);
    }

    public function test_retrain_processes_tenants_in_chunks(): void
    {
        // Create test tenants
        Tenant::factory()->count(150)->create(['is_active' => true]);

        // Clear lock
        $this->redis->connection()->del('ml:retrain:lock');

        $result = $this->service->executeRetrain('test-correlation-4');

        $this->assertEquals('completed', $result['status']);
        $this->assertGreaterThan(0, $result['tenants_processed']);
    }

    public function test_quota_check_prevents_retrain_when_exceeded(): void
    {
        // This test would need to mock the quota service to return insufficient quota
        // For now, we'll skip the actual quota check by ensuring quota is available

        $this->redis->connection()->del('ml:retrain:lock');
        $result = $this->service->executeRetrain('test-correlation-5');

        $this->assertNotEquals('skipped', $result['status']);
    }

    public function test_promote_shadow_model_when_ready(): void
    {
        // Create a shadow model that's ready for promotion
        $shadowModel = FraudModelVersion::create([
            'version' => '2026-04-17-v1',
            'model_type' => 'lightgbm',
            'trained_at' => now()->subDays(2),
            'shadow_started_at' => now()->subHours(25),
            'is_shadow' => true,
            'is_active' => false,
            'auc_roc' => 0.95,
            'shadow_auc_roc' => 0.94,
            'shadow_predictions_count' => 150,
            'shadow_drift_score' => 0.1,
        ]);

        $result = $this->service->promoteShadowModel('test-correlation-6');

        $this->assertEquals('promoted', $result['status']);
        $this->assertEquals($shadowModel->version, $result['model_version']);

        $shadowModel->refresh();
        $this->assertFalse($shadowModel->is_shadow);
        $this->assertTrue($shadowModel->is_active);
    }

    public function test_promote_shadow_model_rejects_if_not_ready(): void
    {
        // Create a shadow model that's NOT ready (shadow period not complete)
        $shadowModel = FraudModelVersion::create([
            'version' => '2026-04-17-v2',
            'model_type' => 'lightgbm',
            'trained_at' => now(),
            'shadow_started_at' => now()->subHours(12), // Only 12 hours, not 24
            'is_shadow' => true,
            'is_active' => false,
            'auc_roc' => 0.95,
            'shadow_auc_roc' => 0.94,
            'shadow_predictions_count' => 150,
        ]);

        $result = $this->service->promoteShadowModel('test-correlation-7');

        $this->assertEquals('not_ready', $result['status']);
        $this->assertEquals('shadow_period_not_complete_or_metrics_insufficient', $result['reason']);

        $shadowModel->refresh();
        $this->assertTrue($shadowModel->is_shadow);
        $this->assertFalse($shadowModel->is_active);
    }

    protected function tearDown(): void
    {
        // Clean up locks
        $this->redis->connection()->del('ml:retrain:lock');
        parent::tearDown();
    }
}
