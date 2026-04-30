<?php

declare(strict_types=1);

namespace Tests\Fraud;

final class AnalyticsFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_event_injection(): void
    {
        $userId = 1;

        // Simulate injection of fake analytics events
        $this->fraudControl->check([
            'operation_type' => 'analytics_event_injection',
            'user_id' => $userId,
            'event_count' => 10000,
            'time_window_seconds' => 10,
            'suspicious_patterns' => true,
            'correlation_id' => 'test_analytics_001',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::FakeDataInjection,
            FraudSeverity::Critical
        );
    }

    public function test_metrics_manipulation(): void
    {
        $userId = 1;
        $sellerId = 100;

        $this->fraudControl->check([
            'operation_type' => 'analytics_metrics_manipulation',
            'user_id' => $userId,
            'seller_id' => $sellerId,
            'metric_type' => 'revenue',
            'original_value' => 1000,
            'manipulated_value' => 1000000,
            'correlation_id' => 'test_analytics_002',
        ]);

        $this->assertFraudAlertCreated(
            'seller',
            $sellerId,
            FraudType::MetricsManipulation,
            FraudSeverity::High
        );
    }

    public function test_unauthorized_data_access(): void
    {
        $userId = 1;
        $targetUserId = 999;

        $this->fraudControl->check([
            'operation_type' => 'analytics_unauthorized_access',
            'user_id' => $userId,
            'target_user_id' => $targetUserId,
            'access_level' => 'admin',
            'required_level' => 'viewer',
            'correlation_id' => 'test_analytics_003',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::UnauthorizedAccess,
            FraudSeverity::High
        );
    }

    public function test_report_export_abuse(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'analytics_export_abuse',
            'user_id' => $userId,
            'export_count' => 50,
            'time_window_minutes' => 5,
            'data_size_mb' => 5000,
            'correlation_id' => 'test_analytics_004',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceAbuse,
            FraudSeverity::Medium
        );
    }

    public function test_funnel_manipulation(): void
    {
        $userId = 1;
        $funnelId = 123;

        $this->fraudControl->check([
            'operation_type' => 'analytics_funnel_manipulation',
            'user_id' => $userId,
            'funnel_id' => $funnelId,
            'conversion_rate' => 0.99, // Suspiciously high
            'expected_rate' => 0.05,
            'correlation_id' => 'test_analytics_005',
        ]);

        $this->assertFraudAlertCreated(
            'funnel',
            $funnelId,
            FraudType::MetricsManipulation,
            FraudSeverity::High
        );
    }
}
