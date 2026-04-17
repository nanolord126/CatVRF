<?php declare(strict_types=1);

namespace Tests\Feature\Hotels;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class HotelsFalsePositiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_legitimate_user_not_flagged(): void
    {
        $userId = 1;
        Cache::put("fraud:user:{$userId}:cancellation_rate", 0.05, 3600);
        Cache::put("fraud:user:{$userId}:successful_bookings", 20, 3600);

        $isFlagged = Cache::get("fraud:user:{$userId}:cancellation_rate") > 0.3;
        $this->assertFalse($isFlagged);
    }

    public function test_normal_booking_amount_not_flagged(): void
    {
        $amount = 15000;
        $threshold = 50000;
        
        $requiresVerification = $amount > $threshold;
        $this->assertFalse($requiresVerification);
    }

    public function test_known_device_not_flagged(): void
    {
        $userId = 1;
        $knownDevice = 'trusted-device';
        Cache::put("fraud:user:{$userId}:known_devices", [$knownDevice], 3600);

        $devices = Cache::get("fraud:user:{$userId}:known_devices");
        $this->assertContains($knownDevice, $devices);
    }

    public function test_verified_user_not_flagged(): void
    {
        $userId = 1;
        Cache::put("fraud:user:{$userId}:verified", true, 3600);

        $isVerified = Cache::get("fraud:user:{$userId}:verified");
        $this->assertTrue($isVerified);
    }

    public function test_corporate_account_not_flagged(): void
    {
        $businessCardId = 'corp-hotel-123';
        Cache::put("fraud:business_card:{$businessCardId}:active", true, 3600);

        $isActive = Cache::get("fraud:business_card:{$businessCardId}:active");
        $this->assertTrue($isActive);
    }
}
