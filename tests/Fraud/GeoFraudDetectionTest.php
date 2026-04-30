<?php

declare(strict_types=1);

namespace Tests\Fraud;

final class GeoFraudDetectionTest extends BaseFraudTest
{
    public function test_location_spoofing(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'geo_spoofing',
            'user_id' => $userId,
            'reported_location' => 'Moscow',
            'detected_location' => 'New York',
            'ip_location' => 'Tokyo',
            'gps_mismatch' => true,
            'correlation_id' => 'test_geo_001',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::LocationSpoofing,
            FraudSeverity::High
        );
    }

    public function test_impossible_travel(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'impossible_travel',
            'user_id' => $userId,
            'location1' => 'Moscow',
            'location2' => 'London',
            'time_diff_minutes' => 10,
            'distance_km' => 2500,
            'speed_kmh' => 15000,
            'correlation_id' => 'test_geo_002',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::LocationSpoofing,
            FraudSeverity::Critical
        );
    }

    public function test_geofence_breach(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'geofence_breach',
            'user_id' => $userId,
            'allowed_region' => 'Russia',
            'detected_region' => 'USA',
            'service_restricted' => true,
            'correlation_id' => 'test_geo_003',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::UnauthorizedAccess,
            FraudSeverity::High
        );
    }

    public function test_proxy_detection(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'proxy_usage',
            'user_id' => $userId,
            'is_proxy' => true,
            'is_vpn' => true,
            'is_tor' => false,
            'risk_score' => 0.9,
            'correlation_id' => 'test_geo_004',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::SuspiciousActivity,
            FraudSeverity::Medium
        );
    }

    public function test_location_velocity_anomaly(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'velocity_anomaly',
            'user_id' => $userId,
            'checkins' => [
                ['location' => 'Moscow', 'time' => '2024-01-01 10:00:00'],
                ['location' => 'St. Petersburg', 'time' => '2024-01-01 10:30:00'],
                ['location' => 'Sochi', 'time' => '2024-01-01 11:00:00'],
            ],
            'avg_velocity_kmh' => 800,
            'correlation_id' => 'test_geo_005',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::LocationSpoofing,
            FraudSeverity::High
        );
    }
}
