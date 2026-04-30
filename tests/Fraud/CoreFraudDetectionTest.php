<?php

declare(strict_types=1);

namespace Tests\Fraud;

final class CoreFraudDetectionTest extends BaseFraudTest
{
    public function test_session_hijacking(): void
    {
        $userId = 1;
        $sessionId = 'sess_hijacked_123';

        $this->fraudControl->check([
            'operation_type' => 'session_hijack',
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_change' => true,
            'user_agent_change' => true,
            'location_change' => true,
            'correlation_id' => 'test_core_001',
        ]);

        $this->assertFraudAlertCreated(
            'session',
            1,
            FraudType::SessionHijacking,
            FraudSeverity::Critical
        );
    }

    public function test_token_reuse_attack(): void
    {
        $userId = 1;
        $token = 'token_reused_123';

        $this->fraudControl->check([
            'operation_type' => 'token_reuse',
            'user_id' => $userId,
            'token' => $token,
            'reuse_count' => 100,
            'time_window_minutes' => 5,
            'correlation_id' => 'test_core_002',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ReplayAttack,
            FraudSeverity::Critical
        );
    }

    public function test_api_rate_limit_bypass(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'rate_limit_bypass',
            'user_id' => $userId,
            'request_count' => 10000,
            'limit' => 100,
            'bypass_method' => 'header_manipulation',
            'correlation_id' => 'test_core_003',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceAbuse,
            FraudSeverity::Critical
        );
    }

    public function test_privilege_escalation_attempt(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'privilege_escalation',
            'user_id' => $userId,
            'current_role' => 'user',
            'attempted_role' => 'admin',
            'method' => 'parameter_tampering',
            'correlation_id' => 'test_core_004',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::UnauthorizedAccess,
            FraudSeverity::Critical
        );
    }

    public function test_credential_stuffing(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'credential_stuffing',
            'user_id' => $userId,
            'login_attempts' => 500,
            'time_window_minutes' => 10,
            'success_rate' => 0.01,
            'known_compromised_credentials' => true,
            'correlation_id' => 'test_core_005',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::CredentialStuffing,
            FraudSeverity::Critical
        );
    }
}
