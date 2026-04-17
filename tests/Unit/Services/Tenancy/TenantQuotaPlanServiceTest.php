<?php declare(strict_types=1);

namespace Tests\Unit\Services\Tenancy;

use App\Services\Tenancy\TenantQuotaPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tenant Quota Plan Service Test
 *
 * Production 2026 CANON - Quota Plan Management Tests
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class TenantQuotaPlanServiceTest extends TestCase
{
    use RefreshDatabase;

    private TenantQuotaPlanService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TenantQuotaPlanService::class);
    }

    public function test_free_plan_quotas(): void
    {
        $quotas = $this->service->getPlanQuotas('free');

        $this->assertEquals(10000, $quotas['ai_tokens']);
        $this->assertEquals(1000, $quotas['redis_ops']);
        $this->assertEquals(500, $quotas['db_queries']);
        $this->assertEquals(100 * 1024 * 1024, $quotas['storage_bytes']);
    }

    public function test_starter_plan_quotas(): void
    {
        $quotas = $this->service->getPlanQuotas('starter');

        $this->assertEquals(100000, $quotas['ai_tokens']);
        $this->assertEquals(10000, $quotas['redis_ops']);
        $this->assertEquals(5000, $quotas['db_queries']);
        $this->assertEquals(1024 * 1024 * 1024, $quotas['storage_bytes']);
    }

    public function test_pro_plan_quotas(): void
    {
        $quotas = $this->service->getPlanQuotas('pro');

        $this->assertEquals(1000000, $quotas['ai_tokens']);
        $this->assertEquals(100000, $quotas['redis_ops']);
        $this->assertEquals(50000, $quotas['db_queries']);
        $this->assertEquals(10 * 1024 * 1024 * 1024, $quotas['storage_bytes']);
    }

    public function test_enterprise_plan_unlimited_quotas(): void
    {
        $quotas = $this->service->getPlanQuotas('enterprise');

        $this->assertEquals(PHP_INT_MAX, $quotas['ai_tokens']);
        $this->assertEquals(PHP_INT_MAX, $quotas['redis_ops']);
        $this->assertEquals(PHP_INT_MAX, $quotas['db_queries']);
    }

    public function test_apply_plan_sets_quotas(): void
    {
        $tenantId = 1;

        $this->service->applyPlan($tenantId, 'starter');

        $limiter = app(\App\Services\Tenancy\TenantResourceLimiterService::class);
        $stats = $limiter->getQuotaStats($tenantId);

        $this->assertEquals(100000, $stats['ai_tokens']['quota']);
    }

    public function test_get_available_plans(): void
    {
        $plans = $this->service->getAvailablePlans();

        $this->assertArrayHasKey('free', $plans);
        $this->assertArrayHasKey('starter', $plans);
        $this->assertArrayHasKey('pro', $plans);
        $this->assertArrayHasKey('enterprise', $plans);

        $this->assertEquals(0, $plans['free']['price']);
        $this->assertEquals(4900, $plans['starter']['price']);
        $this->assertEquals(14900, $plans['pro']['price']);
        $this->assertNull($plans['enterprise']['price']);
    }

    public function test_upgrade_plan_increases_quotas(): void
    {
        $tenantId = 1;

        $this->service->applyPlan($tenantId, 'free');

        $limiter = app(\App\Services\Tenancy\TenantResourceLimiterService::class);
        $statsBefore = $limiter->getQuotaStats($tenantId);

        $this->service->upgradePlan($tenantId, 'pro');

        $statsAfter = $limiter->getQuotaStats($tenantId);

        $this->assertGreaterThan($statsBefore['ai_tokens']['quota'], $statsAfter['ai_tokens']['quota']);
    }
}
