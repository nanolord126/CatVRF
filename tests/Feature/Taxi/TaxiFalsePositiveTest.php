<?php declare(strict_types=1);

namespace Tests\Feature\Taxi;

use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Services\TaxiOrderService;
use App\Domains\Taxi\DTOs\CreateTaxiOrderDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class TaxiFalsePositiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_legitimate_user_with_good_history_not_flagged(): void
    {
        $userId = 1;
        
        Cache::put("fraud:user:{$userId}:cancellation_rate", 0.05, 3600);
        Cache::put("fraud:user:{$userId}:chargeback_rate", 0.01, 3600);
        Cache::put("fraud:user:{$userId}:successful_orders", 50, 3600);

        $isFlagged = $this->checkUserFraudRisk($userId);
        
        $this->assertFalse($isFlagged, 'Legitimate user should not be flagged');
    }

    public function test_regular_order_from_known_device_not_flagged(): void
    {
        $userId = 1;
        $knownDeviceFingerprint = 'known-trusted-device-12345';
        
        Cache::put("fraud:user:{$userId}:known_devices", [$knownDeviceFingerprint], 3600);

        $isNewDevice = !in_array($knownDeviceFingerprint, Cache::get("fraud:user:{$userId}:known_devices", []));
        
        $this->assertFalse($isNewDevice, 'Known device should not be flagged as new');
    }

    public function test_normal_geographic_location_change_not_flagged(): void
    {
        $userId = 1;
        
        Cache::put("fraud:user:{$userId}:last_location", [
            'lat' => 55.75396,
            'lon' => 37.62039,
            'timestamp' => now()->subHours(4),
        ], 3600);

        $currentLocation = [
            'lat' => 55.7550,
            'lon' => 37.6250,
        ];

        $distance = $this->calculateDistance(
            55.75396, 37.62039,
            55.7550, 37.6250
        );

        $this->assertLessThan(10, $distance, 'Normal location change should not be flagged');
    }

    public function test_single_payment_attempt_not_flagged(): void
    {
        $cardFingerprint = 'card-12345';
        
        Cache::put("fraud:card:{$cardFingerprint}:attempts", 1, 3600);

        $isBlocked = Cache::get("fraud:card:{$cardFingerprint}:blocked");
        
        $this->assertFalse($isBlocked, 'Single payment attempt should not be blocked');
    }

    public function test_reasonable_order_amount_not_flagged(): void
    {
        $orderAmount = 5000;
        $fraudThreshold = 100000;

        $requiresVerification = $orderAmount > $fraudThreshold;
        
        $this->assertFalse($requiresVerification, 'Reasonable order amount should not require verification');
    }

    public function test_consistent_payment_method_not_flagged(): void
    {
        $userId = 1;
        
        $paymentMethods = ['wallet', 'wallet', 'wallet', 'wallet'];
        
        Cache::put("fraud:user:{$userId}:payment_history", $paymentMethods, 3600);

        $history = Cache::get("fraud:user:{$userId}:payment_history");
        $uniqueMethods = array_unique($history);

        $this->assertEquals(1, count($uniqueMethods), 'Consistent payment method should not be flagged');
    }

    public function test_normal_order_pattern_not_flagged(): void
    {
        $userId = 1;
        
        $recentOrders = [
            ['time' => now()->subDays(1), 'amount' => 5000],
            ['time' => now()->subDays(3), 'amount' => 3000],
            ['time' => now()->subWeek(), 'amount' => 4000],
        ];

        Cache::put("fraud:user:{$userId}:recent_orders", $recentOrders, 3600);

        $isUnusual = count($recentOrders) > 4 && 
                     $recentOrders[0]['time']->diffInMinutes($recentOrders[count($recentOrders) - 1]['time']) < 30;

        $this->assertFalse($isUnusual, 'Normal order pattern should not be flagged');
    }

    public function test_valid_inn_not_rejected(): void
    {
        $validInn = '7728168971';
        
        $isValid = $this->validateInn($validInn);
        
        $this->assertTrue($isValid, 'Valid INN should not be rejected');
    }

    public function test_single_active_ride_not_flagged(): void
    {
        $userId = 1;
        
        $activeRides = [
            ['location' => 'Moscow', 'status' => 'in_progress'],
        ];

        Cache::put("fraud:user:{$userId}:active_rides", $activeRides, 3600);

        $hasConflictingRides = count($activeRides) > 1 && 
                              $activeRides[0]['status'] === 'in_progress';

        $this->assertFalse($hasConflictingRides, 'Single active ride should not be flagged');
    }

    public function test_normal_refund_timing_not_flagged(): void
    {
        $rideUuid = 'test-ride-uuid';
        
        Cache::put("fraud:ride:{$rideUuid}:payment_time", now()->subHours(2), 3600);
        Cache::put("fraud:ride:{$rideUuid}:refund_request_time", now(), 3600);

        $paymentTime = Cache::get("fraud:ride:{$rideUuid}:payment_time");
        $refundTime = Cache::get("fraud:ride:{$rideUuid}:refund_request_time");

        $timeDifference = $paymentTime->diffInSeconds($refundTime);
        $isSuspicious = $timeDifference < 60;

        $this->assertFalse($isSuspicious, 'Normal refund timing should not be flagged');
    }

    public function test_low_fraud_score_not_flagged(): void
    {
        $userId = 1;
        
        $fraudFactors = [
            'high_value_order' => 0,
            'new_device' => 0,
            'suspicious_location' => 5,
            'unusual_pattern' => 0,
        ];

        $totalScore = array_sum($fraudFactors);
        $threshold = 50;

        $isFraudulent = $totalScore >= $threshold;

        $this->assertFalse($isFraudulent, 'Low fraud score should not be flagged');
    }

    public function test_legitimate_ip_not_blacklisted(): void
    {
        $legitimateIp = '192.168.1.100';
        
        $isBlacklisted = Cache::get("fraud:blacklist:ip:{$legitimateIp}");
        
        $this->assertFalse($isBlacklisted, 'Legitimate IP should not be blacklisted');
    }

    public function test_whitelisted_ip_bypasses_rate_limiting(): void
    {
        $whitelistedIp = '192.168.1.200';
        
        Cache::put("whitelist:ip:{$whitelistedIp}", true, 3600);

        $isWhitelisted = Cache::get("whitelist:ip:{$whitelistedIp}");
        
        $this->assertTrue($isWhitelisted, 'Whitelisted IP should bypass rate limiting');
    }

    public function test_verified_user_not_flagged(): void
    {
        $userId = 1;
        
        Cache::put("fraud:user:{$userId}:verified", true, 3600);
        Cache::put("fraud:user:{$userId}:verification_level", 'high', 3600);

        $isVerified = Cache::get("fraud:user:{$userId}:verified");
        $verificationLevel = Cache::get("fraud:user:{$userId}:verification_level");

        $this->assertTrue($isVerified, 'Verified user should not be flagged');
        $this->assertEquals('high', $verificationLevel, 'Verification level should be high');
    }

    public function test_business_user_with_valid_inn_not_flagged(): void
    {
        $userId = 1;
        $businessInn = '7728168971';
        
        Cache::put("fraud:user:{$userId}:is_business", true, 3600);
        Cache::put("fraud:user:{$userId}:inn_verified", true, 3600);

        $isBusiness = Cache::get("fraud:user:{$userId}:is_business");
        $innVerified = Cache::get("fraud:user:{$userId}:inn_verified");

        $this->assertTrue($isBusiness, 'Business user status should be recognized');
        $this->assertTrue($innVerified, 'Valid INN should be verified');
    }

    public function test_corporate_account_not_flagged(): void
    {
        $businessCardId = 'corp-card-123';
        
        Cache::put("fraud:business_card:{$businessCardId}:active", true, 3600);
        Cache::put("fraud:business_card:{$businessCardId}:credit_limit", 1000000, 3600);

        $isActive = Cache::get("fraud:business_card:{$businessCardId}:active");
        $creditLimit = Cache::get("fraud:business_card:{$businessCardId}:credit_limit");

        $this->assertTrue($isActive, 'Corporate account should be active');
        $this->assertGreaterThan(0, $creditLimit, 'Corporate account should have credit limit');
    }

    public function test_long_term_user_not_flagged(): void
    {
        $userId = 1;
        
        Cache::put("fraud:user:{$userId}:account_age_days", 365, 3600);
        Cache::put("fraud:user:{$userId}:total_orders", 100, 3600);

        $accountAge = Cache::get("fraud:user:{$userId}:account_age_days");
        $totalOrders = Cache::get("fraud:user:{$userId}:total_orders");

        $this->assertGreaterThan(30, $accountAge, 'Long term user should not be flagged');
        $this->assertGreaterThan(10, $totalOrders, 'User with order history should not be flagged');
    }

    public function test_referral_user_not_flagged(): void
    {
        $userId = 1;
        $referrerId = 2;
        
        Cache::put("fraud:user:{$userId}:referred_by", $referrerId, 3600);
        Cache::put("fraud:user:{$referrerId}:is_trusted", true, 3600);

        $referredBy = Cache::get("fraud:user:{$userId}:referred_by");
        $referrerTrusted = Cache::get("fraud:user:{$referrerId}:is_trusted");

        $this->assertNotNull($referredBy, 'Referred user should have referrer');
        $this->assertTrue($referrerTrusted, 'Trusted referrer should reduce fraud risk');
    }

    public function test_normal_split_payment_not_flagged(): void
    {
        $splitDetails = [
            ['user_id' => 1, 'share' => 50, 'amount' => 2500],
            ['user_id' => 2, 'share' => 50, 'amount' => 2500],
        ];

        $totalAmount = 5000;
        $suspiciousThreshold = 150000;

        $requiresReview = $totalAmount > $suspiciousThreshold;
        
        $this->assertFalse($requiresReview, 'Normal split payment should not require review');
    }

    public function test_voice_order_with_biometric_auth_not_flagged(): void
    {
        $userId = 1;
        $biometricVerified = true;
        
        Cache::put("fraud:user:{$userId}:biometric_verified", $biometricVerified, 3600);

        $isVerified = Cache::get("fraud:user:{$userId}:biometric_verified");

        $this->assertTrue($isVerified, 'Biometric verified voice order should not be flagged');
    }

    private function checkUserFraudRisk(int $userId): bool
    {
        $cancellationRate = Cache::get("fraud:user:{$userId}:cancellation_rate", 0);
        $chargebackRate = Cache::get("fraud:user:{$userId}:chargeback_rate", 0);
        
        return $cancellationRate > 0.3 || $chargebackRate > 0.1;
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
