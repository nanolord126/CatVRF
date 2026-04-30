<?php

declare(strict_types=1);

namespace Tests\Fraud;

final class BigDataFraudDetectionTest extends BaseFraudTest
{
    public function test_data_poisoning(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'bigdata_poisoning',
            'user_id' => $userId,
            'dataset' => 'training_data',
            'poisoned_records' => 1000,
            'total_records' => 10000,
            'correlation_id' => 'test_bigdata_001',
        ]);

        $this->assertFraudAlertCreated(
            'dataset',
            1,
            FraudType::DataPoisoning,
            FraudSeverity::Critical
        );
    }

    public function test_query_injection(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'bigdata_query_injection',
            'user_id' => $userId,
            'query' => "SELECT * FROM users WHERE '1'='1' OR 1=1",
            'injection_detected' => true,
            'correlation_id' => 'test_bigdata_002',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::SqlInjection,
            FraudSeverity::Critical
        );
    }

    public function test_unauthorized_export(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'bigdata_export',
            'user_id' => $userId,
            'table' => 'sensitive_user_data',
            'row_count' => 1000000,
            'permission_level' => 'viewer',
            'required_level' => 'admin',
            'correlation_id' => 'test_bigdata_003',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::UnauthorizedAccess,
            FraudSeverity::Critical
        );
    }

    public function test_resource_abuse(): void
    {
        $userId = 1;

        // Simulate excessive query load
        $this->fraudControl->check([
            'operation_type' => 'bigdata_resource_abuse',
            'user_id' => $userId,
            'query_count' => 10000,
            'time_window_minutes' => 5,
            'cpu_usage_percent' => 95,
            'memory_usage_gb' => 50,
            'correlation_id' => 'test_bigdata_004',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceAbuse,
            FraudSeverity::High
        );
    }

    public function test_model_drift_manipulation(): void
    {
        $userId = 1;
        $modelId = 100;

        $this->fraudControl->check([
            'operation_type' => 'bigdata_model_drift',
            'user_id' => $userId,
            'model_id' => $modelId,
            'artificial_drift' => true,
            'drift_magnitude' => 0.9,
            'correlation_id' => 'test_bigdata_005',
        ]);

        $this->assertFraudAlertCreated(
            'model',
            $modelId,
            FraudType::ModelTampering,
            FraudSeverity::Critical
        );
    }
}
