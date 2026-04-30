<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\FraudDetection\Interfaces\Services\HttpFraudScoringService;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CRMFraudDetectionTest extends TestCase
{
    use RefreshDatabase;

    private HttpFraudScoringService $fraudService;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraudService = app(HttpFraudScoringService::class);
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function testDetectSuspiciousOrderAccess(): void
    {
        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'Order',
            'action' => 'bulk_order_access',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'order_count' => 1000,
                'time_period_seconds' => 60,
            ],
        ]);

        $this->assertArrayHasKey('score', $fraudScore);
        $this->assertArrayHasKey('risk_level', $fraudScore);
    }

    public function testDetectStaffDataExfiltration(): void
    {
        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'Staff',
            'action' => 'bulk_staff_export',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'staff_count' => 500,
                'includes_sensitive_data' => true,
            ],
        ]);

        $this->assertGreaterThan(60, $fraudScore['score']);
    }

    public function testDetectUnauthorizedFinancialAccess(): void
    {
        $regularUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'staff',
        ]);

        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'Wallet',
            'action' => 'unauthorized_financial_access',
            'user_id' => $regularUser->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'attempted_action' => 'withdraw',
                'required_role' => 'admin',
            ],
        ]);

        $this->assertEquals('high', $fraudScore['risk_level']);
    }

    public function testDetectDocumentTampering(): void
    {
        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'Document',
            'action' => 'document_tampering',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'document_type' => 'invoice',
                'original_amount' => 10000,
                'modified_amount' => 1000,
            ],
        ]);

        $this->assertGreaterThan(80, $fraudScore['score']);
    }

    public function testDetectMultipleFailedLoginAttempts(): void
    {
        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'User',
            'action' => 'multiple_failed_logins',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'failed_attempts' => 10,
                'time_period_minutes' => 5,
                'ip_addresses' => ['192.168.1.1', '10.0.0.1'],
            ],
        ]);

        $this->assertGreaterThan(70, $fraudScore['score']);
        $this->assertEquals('high', $fraudScore['risk_level']);
    }

    public function testDetectPrivilegeEscalationAttempt(): void
    {
        $staffUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'staff',
        ]);

        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'User',
            'action' => 'privilege_escalation',
            'user_id' => $staffUser->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'current_role' => 'staff',
                'attempted_role' => 'admin',
            ],
        ]);

        $this->assertEquals('critical', $fraudScore['risk_level']);
    }

    public function testDetectDataPatternAnomaly(): void
    {
        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'Analytics',
            'action' => 'data_pattern_anomaly',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'anomaly_type' => 'statistical_outlier',
                'deviation_score' => 5.5,
                'threshold' => 3.0,
            ],
        ]);

        $this->assertGreaterThan(50, $fraudScore['score']);
    }

    public function testDetectBranchSwitchingAbuse(): void
    {
        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'Branch',
            'action' => 'rapid_branch_switching',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'switches_count' => 20,
                'time_period_minutes' => 10,
                'unique_branches' => 5,
            ],
        ]);

        $this->assertGreaterThan(60, $fraudScore['score']);
    }

    public function testFraudScoreCaching(): void
    {
        $transactionData = [
            'entity_type' => 'Order',
            'action' => 'test_action',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ];

        $score1 = $this->fraudService->scoreTransaction($transactionData);
        $score2 = $this->fraudService->scoreTransaction($transactionData);

        $this->assertEquals($score1['score'], $score2['score']);
    }

    public function testFraudAlertGeneration(): void
    {
        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'Order',
            'action' => 'suspicious_activity',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'suspicious_pattern' => true,
            ],
        ]);

        if ($fraudScore['risk_level'] === 'high' || $fraudScore['risk_level'] === 'critical') {
            $this->assertDatabaseHas('fraud_alerts', [
                'user_id' => $this->user->id,
                'tenant_id' => $this->tenant->id,
                'risk_level' => $fraudScore['risk_level'],
            ]);
        }
    }
}
