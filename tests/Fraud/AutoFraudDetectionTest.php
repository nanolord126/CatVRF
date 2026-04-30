<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Auto\Domain\Entities\Vehicle;
use Modules\Auto\Domain\Enums\VehicleStatus;

final class AutoFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_listing_creation(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'auto_listing_creation',
            'user_id' => $userId,
            'listing_count' => 100,
            'time_window_hours' => 1,
            'suspicious_images' => true,
            'duplicate_descriptions' => true,
            'correlation_id' => 'test_auto_001',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::FakeListing,
            FraudSeverity::Critical
        );
    }

    public function test_price_manipulation(): void
    {
        $userId = 1;
        $vehicleId = 500;

        $this->fraudControl->check([
            'operation_type' => 'auto_price_manipulation',
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
            'original_price' => 1000000,
            'new_price' => 10000,
            'price_drop_percent' => 99,
            'correlation_id' => 'test_auto_002',
        ]);

        $this->assertFraudAlertCreated(
            'vehicle',
            $vehicleId,
            FraudType::PriceManipulation,
            FraudSeverity::High
        );
    }

    public function test_mileage_fraud(): void
    {
        $userId = 1;
        $vehicleId = 501;

        $this->fraudControl->check([
            'operation_type' => 'auto_mileage_fraud',
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
            'reported_mileage' => 50000,
            'expected_mileage' => 150000,
            'evidence_tampering' => true,
            'correlation_id' => 'test_auto_003',
        ]);

        $this->assertFraudAlertCreated(
            'vehicle',
            $vehicleId,
            FraudType::DocumentFraud,
            FraudSeverity::High
        );
    }

    public function test_booking_fraud(): void
    {
        $userId = 1;

        // Multiple bookings with no show
        $this->fraudControl->check([
            'operation_type' => 'auto_booking_fraud',
            'user_id' => $userId,
            'booking_count' => 20,
            'no_show_count' => 20,
            'time_window_days' => 7,
            'correlation_id' => 'test_auto_004',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::BookingFraud,
            FraudSeverity::Medium
        );
    }

    public function test_vehicle_theft_attempt(): void
    {
        $userId = 1;
        $vehicleId = 502;

        $this->fraudControl->check([
            'operation_type' => 'auto_theft_attempt',
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
            'ownership_verification_failed' => true,
            'document_mismatch' => true,
            'correlation_id' => 'test_auto_005',
        ]);

        $this->assertFraudAlertCreated(
            'vehicle',
            $vehicleId,
            FraudType::TheftAttempt,
            FraudSeverity::Critical
        );
    }
}
