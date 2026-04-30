<?php

declare(strict_types=1);

namespace Tests\Load;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CRMPanelLoadTest extends TestCase
{
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($this->user);
    }

    public function testCRMOrdersEndpoint5000RPS(): void
    {
        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->get("/api/tenant/{$this->tenant->id}/crm/orders");
        }

        $duration = microtime(true) - $startTime;
        $rps = $iterations / $duration;
        $this->assertGreaterThan(5000, $rps);
    }

    public function testCRMStaffEndpoint5000RPS(): void
    {
        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->get("/api/tenant/{$this->tenant->id}/crm/staff");
        }

        $duration = microtime(true) - $startTime;
        $rps = $iterations / $duration;
        $this->assertGreaterThan(5000, $rps);
    }

    public function testCRMWarehouseEndpoint5000RPS(): void
    {
        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->get("/api/tenant/{$this->tenant->id}/crm/warehouse");
        }

        $duration = microtime(true) - $startTime;
        $rps = $iterations / $duration;
        $this->assertGreaterThan(5000, $rps);
    }

    public function testCRMAnalyticsEndpoint5000RPS(): void
    {
        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->get("/api/tenant/{$this->tenant->id}/crm/analytics");
        }

        $duration = microtime(true) - $startTime;
        $rps = $iterations / $duration;
        $this->assertGreaterThan(5000, $rps);
    }

    public function testCRMWalletEndpoint5000RPS(): void
    {
        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->get("/api/tenant/{$this->tenant->id}/wallet/balance");
        }

        $duration = microtime(true) - $startTime;
        $rps = $iterations / $duration;
        $this->assertGreaterThan(5000, $rps);
    }

    public function testCRMBranchSwitch5000RPS(): void
    {
        $branch = $this->tenant->branches()->create([
            'name' => 'Test Branch',
            'address' => 'Test Address',
        ]);

        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->post("/api/tenant/{$this->tenant->id}/branch/switch", [
                'branch_id' => $branch->id,
            ]);
        }

        $duration = microtime(true) - $startTime;
        $rps = $iterations / $duration;
        $this->assertGreaterThan(5000, $rps);
    }

    public function testCRMDocumentsEndpoint5000RPS(): void
    {
        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->get("/api/tenant/{$this->tenant->id}/wallet/documents");
        }

        $duration = microtime(true) - $startTime;
        $rps = $iterations / $duration;
        $this->assertGreaterThan(5000, $rps);
    }
}
