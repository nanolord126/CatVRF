<?php

declare(strict_types=1);

namespace Tests\Fraud;

final class DemandForecastFraudDetectionTest extends BaseFraudTest
{
    public function test_forecast_manipulation(): void
    {
        $userId = 1;
        $productId = 1000;

        $this->fraudControl->check([
            'operation_type' => 'forecast_manipulation',
            'user_id' => $userId,
            'product_id' => $productId,
            'original_forecast' => 100,
            'manipulated_forecast' => 10000,
            'manipulation_reason' => 'artificial_demand',
            'correlation_id' => 'test_forecast_001',
        ]);

        $this->assertFraudAlertCreated(
            'product',
            $productId,
            FraudType::MetricsManipulation,
            FraudSeverity::High
        );
    }

    public function test_inventory_hoarding(): void
    {
        $userId = 1;
        $sellerId = 500;

        $this->fraudControl->check([
            'operation_type' => 'inventory_hoarding',
            'user_id' => $userId,
            'seller_id' => $sellerId,
            'product_count' => 1000,
            'total_inventory' => 5000,
            'hoarding_percent' => 20,
            'correlation_id' => 'test_forecast_002',
        ]);

        $this->assertFraudAlertCreated(
            'seller',
            $sellerId,
            FraudType::MarketManipulation,
            FraudSeverity::High
        );
    }

    public function test_false_demand_signals(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'false_demand',
            'user_id' => $userId,
            'fake_orders' => 500,
            'time_window_hours' => 2,
            'cancellation_rate' => 0.95,
            'correlation_id' => 'test_forecast_003',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::FakeDataInjection,
            FraudSeverity::Critical
        );
    }

    public function test_price_prediction_exploitation(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'prediction_exploitation',
            'user_id' => $userId,
            'api_calls' => 10000,
            'time_window_minutes' => 10,
            'suspicious_pattern' => 'arbitrage_attempt',
            'correlation_id' => 'test_forecast_004',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceAbuse,
            FraudSeverity::High
        );
    }

    public function test_data_tampering(): void
    {
        $userId = 1;
        $datasetId = 200;

        $this->fraudControl->check([
            'operation_type' => 'data_tampering',
            'user_id' => $userId,
            'dataset_id' => $datasetId,
            'tampered_records' => 1000,
            'total_records' => 10000,
            'correlation_id' => 'test_forecast_005',
        ]);

        $this->assertFraudAlertCreated(
            'dataset',
            $datasetId,
            FraudType::DataPoisoning,
            FraudSeverity::Critical
        );
    }
}
