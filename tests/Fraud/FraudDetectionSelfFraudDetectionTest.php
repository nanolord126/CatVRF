<?php

declare(strict_types=1);

namespace Tests\Fraud;

final class FraudDetectionSelfFraudDetectionTest extends BaseFraudTest
{
    public function test_rule_tampering(): void
    {
        $userId = 1;
        $ruleId = 100;

        $this->fraudControl->check([
            'operation_type' => 'fraud_rule_tampering',
            'user_id' => $userId,
            'rule_id' => $ruleId,
            'original_threshold' => 0.8,
            'tampered_threshold' => 0.0,
            'permission_level' => 'analyst',
            'required_level' => 'admin',
            'correlation_id' => 'test_fraud_self_001',
        ]);

        $this->assertFraudAlertCreated(
            'fraud_rule',
            $ruleId,
            FraudType::UnauthorizedAccess,
            FraudSeverity::Critical
        );
    }

    public function test_alert_suppression(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'alert_suppression',
            'user_id' => $userId,
            'suppressed_alerts' => 50,
            'time_window_hours' => 1,
            'suppression_reason' => 'false_positive_overuse',
            'correlation_id' => 'test_fraud_self_002',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::AuditEvasion,
            FraudSeverity::High
        );
    }

    public function test_model_poisoning(): void
    {
        $userId = 1;
        $modelId = 200;

        $this->fraudControl->check([
            'operation_type' => 'fraud_model_poisoning',
            'user_id' => $userId,
            'model_id' => $modelId,
            'poisoned_samples' => 1000,
            'total_samples' => 10000,
            'target_class' => 'legitimate',
            'correlation_id' => 'test_fraud_self_003',
        ]);

        $this->assertFraudAlertCreated(
            'fraud_model',
            $modelId,
            FraudType::DataPoisoning,
            FraudSeverity::Critical
        );
    }

    public function test_false_positive_manipulation(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'false_positive_manipulation',
            'user_id' => $userId,
            'marked_as_false_positive' => 100,
            'actual_fraud_rate' => 0.8,
            'correlation_id' => 'test_fraud_self_004',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::AuditEvasion,
            FraudSeverity::High
        );
    }

    public function test_whitelist_abuse(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'whitelist_abuse',
            'user_id' => $userId,
            'whitelist_entries_added' => 500,
            'time_window_days' => 1,
            'suspicious_entities' => 450,
            'correlation_id' => 'test_fraud_self_005',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::AuditEvasion,
            FraudSeverity::Critical
        );
    }
}
