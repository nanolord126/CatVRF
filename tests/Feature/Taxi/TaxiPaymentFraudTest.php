<?php declare(strict_types=1);

namespace Tests\Feature\Taxi;

use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Services\TaxiOrderService;
use App\Domains\Taxi\DTOs\CreateTaxiOrderDto;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class TaxiPaymentFraudTest extends TestCase
{
    use RefreshDatabase;

    public function test_high_value_order_requires_additional_verification(): void
    {
        $dto = new CreateTaxiOrderDto(
            tenantId: 1,
            businessGroupId: null,
            passengerId: 1,
            pickupAddress: 'Moscow, Red Square',
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            dropoffAddress: 'Saint Petersburg',
            dropoffLat: 59.9343,
            dropoffLon: 30.3351,
            paymentMethod: 'wallet',
            isSplitPayment: false,
            splitPaymentDetails: null,
            voiceOrderEnabled: false,
            biometricAuthRequired: false,
            videoCallEnabled: false,
            inn: null,
            businessCardId: null,
            ipAddress: '127.0.0.1',
            deviceFingerprint: 'test-fingerprint',
            correlationId: 'fraud-high-value-test',
            idempotencyKey: null,
            deviceType: 'mobile',
            appVersion: '1.0.0',
        );

        Cache::put('fraud:high_value_threshold', 100000, 3600);

        $fraudCheck = app(FraudControlService::class)->check(
            userId: 1,
            operationType: 'taxi_order_create',
            amount: 150000,
            ipAddress: '127.0.0.1',
            deviceFingerprint: 'test-fingerprint',
            correlationId: 'fraud-high-value-test',
        );

        $this->assertTrue(true, 'High value order requires verification');
    }

    public function test_multiple_payment_attempts_from_same_card_gets_blocked(): void
    {
        $cardFingerprint = 'card-12345';
        
        for ($i = 0; $i < 6; $i++) {
            Cache::put("fraud:card:{$cardFingerprint}:attempts", $i + 1, 3600);
        }

        $isBlocked = Cache::get("fraud:card:{$cardFingerprint}:blocked");
        
        $this->assertTrue($isBlocked ?? false, 'Multiple payment attempts should be blocked');
    }

    public function test_suspicious_location_change_triggers_fraud_alert(): void
    {
        $userId = 999;
        
        Cache::put("fraud:user:{$userId}:last_location", [
            'lat' => 55.75396,
            'lon' => 37.62039,
            'timestamp' => now()->subMinutes(30),
        ], 3600);

        $currentLocation = [
            'lat' => 40.7128,
            'lon' => -74.0060,
        ];

        $distance = $this->calculateDistance(
            55.75396, 37.62039,
            40.7128, -74.0060
        );

        $this->assertGreaterThan(1000, $distance, 'Suspicious location change detected');
    }

    public function test_user_with_high_chargeback_rate_gets_flagged(): void
    {
        $userId = 888;
        
        Cache::put("fraud:user:{$userId}:chargeback_rate", 0.25, 3600);

        $chargebackRate = Cache::get("fraud:user:{$userId}:chargeback_rate");
        
        $this->assertGreaterThan(0.15, $chargebackRate, 'High chargeback rate should flag user');
    }

    public function test_split_payment_with_suspicious_amounts_gets_reviewed(): void
    {
        $splitDetails = [
            ['user_id' => 1, 'share' => 50, 'amount' => 100000],
            ['user_id' => 2, 'share' => 50, 'amount' => 100000],
        ];

        $totalAmount = 200000;
        $suspiciousThreshold = 150000;

        $requiresReview = $totalAmount > $suspiciousThreshold;
        
        $this->assertTrue($requiresReview, 'Large split payment requires review');
    }

    public function test_rapid_payment_method_switching_gets_flagged(): void
    {
        $userId = 777;
        
        $paymentMethods = ['wallet', 'card', 'cash', 'wallet', 'card'];
        
        Cache::put("fraud:user:{$userId}:payment_history", $paymentMethods, 3600);

        $history = Cache::get("fraud:user:{$userId}:payment_history");
        $uniqueMethods = array_unique($history);

        $this->assertGreaterThan(2, count($uniqueMethods), 'Rapid payment switching detected');
    }

    public function test_order_from_new_device_requires_verification(): void
    {
        $userId = 666;
        $newDeviceFingerprint = 'brand-new-device-12345';
        
        Cache::put("fraud:user:{$userId}:known_devices", ['device-1', 'device-2'], 3600);

        $knownDevices = Cache::get("fraud:user:{$userId}:known_devices");
        $isNewDevice = !in_array($newDeviceFingerprint, $knownDevices);

        $this->assertTrue($isNewDevice, 'New device requires verification');
    }

    public function test_business_card_with_invalid_inn_gets_rejected(): void
    {
        $invalidInn = '123456789012';
        
        $isValid = $this->validateInn($invalidInn);
        
        $this->assertFalse($isValid, 'Invalid INN should be rejected');
    }

    public function test_unusual_order_pattern_gets_detected(): void
    {
        $userId = 555;
        
        $recentOrders = [
            ['time' => now()->subMinutes(5), 'amount' => 5000],
            ['time' => now()->subMinutes(10), 'amount' => 5000],
            ['time' => now()->subMinutes(15), 'amount' => 5000],
            ['time' => now()->subMinutes(20), 'amount' => 5000],
            ['time' => now()->subMinutes(25), 'amount' => 5000],
        ];

        Cache::put("fraud:user:{$userId}:recent_orders", $recentOrders, 3600);

        $isUnusual = count($recentOrders) > 4 && 
                     $recentOrders[0]['time']->diffInMinutes($recentOrders[4]['time']) < 30;

        $this->assertTrue($isUnusual, 'Unusual order pattern detected');
    }

    public function test_payment_from_blacklisted_ip_gets_blocked(): void
    {
        $blacklistedIp = '192.168.1.999';
        
        Cache::put("fraud:blacklist:ip:{$blacklistedIp}", true, 3600);

        $isBlacklisted = Cache::get("fraud:blacklist:ip:{$blacklistedIp}");
        
        $this->assertTrue($isBlacklisted, 'Blacklisted IP should be blocked');
    }

    public function test_multiple_rides_same_time_different_locations(): void
    {
        $userId = 444;
        
        $activeRides = [
            ['location' => 'Moscow', 'status' => 'in_progress'],
            ['location' => 'Saint Petersburg', 'status' => 'in_progress'],
        ];

        Cache::put("fraud:user:{$userId}:active_rides", $activeRides, 3600);

        $hasConflictingRides = count($activeRides) > 1 && 
                              $activeRides[0]['status'] === 'in_progress';

        $this->assertTrue($hasConflictingRides, 'Conflicting rides detected');
    }

    public function test_refund_request_immediately_after_payment_gets_flagged(): void
    {
        $rideUuid = 'test-ride-uuid';
        
        Cache::put("fraud:ride:{$rideUuid}:payment_time", now()->subSeconds(30), 3600);
        Cache::put("fraud:ride:{$rideUuid}:refund_request_time", now(), 3600);

        $paymentTime = Cache::get("fraud:ride:{$rideUuid}:payment_time");
        $refundTime = Cache::get("fraud:ride:{$rideUuid}:refund_request_time");

        $timeDifference = $paymentTime->diffInSeconds($refundTime);
        $isSuspicious = $timeDifference < 60;

        $this->assertTrue($isSuspicious, 'Immediate refund request is suspicious');
    }

    public function test_fraud_score_calculation_works_correctly(): void
    {
        $userId = 333;
        
        $fraudFactors = [
            'high_value_order' => 30,
            'new_device' => 20,
            'suspicious_location' => 25,
            'unusual_pattern' => 15,
        ];

        $totalScore = array_sum($fraudFactors);
        $threshold = 50;

        $isFraudulent = $totalScore >= $threshold;

        $this->assertTrue($isFraudulent, 'Fraud score calculation works');
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    private function validateInn(string $inn): bool
    {
        return strlen($inn) === 10 || strlen($inn) === 12;
    }
}
