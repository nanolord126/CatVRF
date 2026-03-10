<?php

namespace Tests\Unit\Security;

use Tests\TestCase;
use App\Services\Common\Security\AIAnomalyDetector;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AIAnomalyDetectorTest extends TestCase
{
    use RefreshDatabase;

    protected AIAnomalyDetector $detector;
    protected string $tenantId;
    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        // В SQLite in-memory в тестах отключаем FK
        DB::statement("PRAGMA foreign_keys = OFF");
        
        $this->detector = new AIAnomalyDetector();
        
        $this->tenantId = "tenant-" . Str::random(8);
        
        DB::table("tenants")->insert([
            "id" => $this->tenantId,
            "name" => "Security Test Tenant",
            "type" => "standard",
            "data" => json_encode([]),
            "created_at" => now(),
            "updated_at" => now()
        ]);

        $this->userId = DB::table("users")->insertGetId([
            "name" => "Sec User",
            "email" => "sec-" . Str::random(5) . "@audit.com",
            "password" => "nopass",
            "tenant_id" => $this->tenantId,
            "created_at" => now(),
            "updated_at" => now()
        ]);
    }

    /** @test */
    public function it_identifies_velocity_anomaly()
    {
        $action = "test_action";
        for ($i = 0; $i < 11; $i++) {
            DB::table("fraud_events")->insert([
                "tenant_id" => $this->tenantId,
                "user_id" => $this->userId,
                "event_type" => $action,
                "payload" => json_encode([]),
                "risk_score" => 10.0,
                "correlation_id" => "v-" . $i,
                "created_at" => now(),
            ]);
        }

        // Прямое создание модели для теста
        $tenant = new Tenant();
        $tenant->id = $this->tenantId;
        $tenant->exists = true;

        $score = $this->detector->analyze($tenant, $this->userId, $action, []);
        $this->assertEquals(40.0, $score);
    }

    /** @test */
    public function it_identifies_financial_anomaly()
    {
        $action = "wallet_transfer";
        $tenant = new Tenant();
        $tenant->id = $this->tenantId;
        $tenant->exists = true;

        $score = $this->detector->analyze($tenant, $this->userId, $action, ["amount" => 10000]);
        $this->assertEquals(25.0, $score);
    }

    /** @test */
    public function it_automatically_blocks_high_risk_actions()
    {
        $action = "critical_mutation";
        for ($i = 0; $i < 15; $i++) {
            DB::table("fraud_events")->insert([
                "tenant_id" => $this->tenantId,
                "user_id" => $this->userId,
                "event_type" => $action,
                "payload" => json_encode([]),
                "risk_score" => 50.0,
                "correlation_id" => "b-" . $i,
                "created_at" => now(),
            ]);
        }

        $tenant = new Tenant();
        $tenant->id = $this->tenantId;
        $tenant->exists = true;

        $score = $this->detector->analyze($tenant, $this->userId, $action, ["amount" => 10000]);
        $this->assertGreaterThanOrEqual(65.0, $score);
    }
}
