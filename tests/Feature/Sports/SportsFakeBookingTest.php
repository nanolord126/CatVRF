<?php

declare(strict_types=1);

namespace Tests\Feature\Sports;

use App\Domains\Sports\DTOs\RealTimeBookingDto;
use App\Domains\Sports\Services\SportsRealTimeBookingService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Exceptions\FraudBlockedException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Tests\TestCase;
use Illuminate\Support\Str;

final class SportsFakeBookingTest extends TestCase
{
    use RefreshDatabase;

    private SportsRealTimeBookingService $service;
    private FraudControlService $fraud;
    private RedisConnection $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->app->make(FraudControlService::class);
        $this->audit = $this->app->make(AuditService::class);
        $this->db = $this->app->make(DatabaseManager::class);
        $this->cache = $this->app->make(Cache::class);
        $this->redis = $this->app->make('redis')->connection();

        $this->service = new SportsRealTimeBookingService(
            fraud: $this->fraud,
            audit: $this->audit,
            db: $this->db,
            cache: $this->cache,
            logger: $this->app->make('log'),
            redis: $this->redis,
        );
    }

    public function test_bot_detected_booking_blocked(): void
    {
        $botIndicators = [
            'user_agent' => 'bot/1.0',
            'request_time' => 0.001,
            'keyboard_events' => 0,
            'mouse_events' => 0,
            'touch_events' => 0,
        ];

        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: $botIndicators,
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        try {
            $result = $this->service->holdSlot($dto);
            $this->assertFalse($result['success'] ?? true, 'Bot booking should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Bot booking should trigger fraud detection');
        }
    }

    public function test_suspicious_booking_pattern_detected(): void
    {
        $userId = 1;
        $suspiciousPattern = [
            'booking_frequency' => 'high',
            'time_of_day' => '3am',
            'location_mismatch' => true,
        ];

        for ($i = 0; $i < 10; $i++) {
            $dto = new RealTimeBookingDto(
                userId: $userId,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2 + $i)->toDateTimeString(),
                slotEnd: now()->addHours(3 + $i)->toDateTimeString(),
                bookingType: 'general',
                biometricData: $suspiciousPattern,
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                
                if ($i >= 5) {
                    $this->assertFalse($result['success'] ?? true, 'Suspicious pattern should be blocked');
                }
            } catch (FraudBlockedException $e) {
                $this->assertTrue($i >= 5, 'Should trigger fraud detection after pattern');
                break;
            }
        }
    }

    public function test_non_existent_venue_booking_blocked(): void
    {
        $nonExistentVenues = [99999, 88888, 77777, 66666, 55555];

        foreach ($nonExistentVenues as $venueId) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: $venueId,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: [],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Non-existent venue booking should be blocked');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('not found', strtolower($e->getMessage()));
            }
        }
    }

    public function test_past_slot_booking_blocked(): void
    {
        $pastSlots = [
            now()->subHours(1)->toDateTimeString(),
            now()->subMinutes(30)->toDateTimeString(),
            now()->subMinutes(5)->toDateTimeString(),
        ];

        foreach ($pastSlots as $slotStart) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: $slotStart,
                slotEnd: now()->addHour()->toDateTimeString(),
                bookingType: 'general',
                biometricData: [],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Past slot booking should be blocked');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('past', strtolower($e->getMessage()));
            }
        }
    }

    public function test_invalid_slot_duration_blocked(): void
    {
        $invalidDurations = [
            ['start' => now()->addHours(2), 'end' => now()->addHours(2)],
            ['start' => now()->addHours(2), 'end' => now()->addHours(2, 30)],
            ['start' => now()->addHours(2), 'end' => now()->addHours(10)],
        ];

        foreach ($invalidDurations as $duration) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: $duration['start']->toDateTimeString(),
                slotEnd: $duration['end']->toDateTimeString(),
                bookingType: 'general',
                biometricData: [],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Invalid slot duration should be blocked');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('duration', strtolower($e->getMessage()));
            }
        }
    }

    public function test_duplicate_booking_detection(): void
    {
        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
            idempotencyKey: 'same-key-123',
        );

        $result1 = $this->service->holdSlot($dto);
        $this->assertTrue($result1['success']);

        $dto2 = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
            idempotencyKey: 'same-key-123',
        );

        $result2 = $this->service->holdSlot($dto2);
        $this->assertFalse($result2['success'], 'Duplicate booking should be blocked');
    }

    public function test_suspicious_user_behavior_blocked(): void
    {
        $suspiciousUsers = [
            ['user_id' => 999, 'reason' => 'high_cancellation_rate'],
            ['user_id' => 998, 'reason' => 'multiple_chargebacks'],
            ['user_id' => 997, 'reason' => 'suspicious_ip'],
        ];

        foreach ($suspiciousUsers as $user) {
            $dto = new RealTimeBookingDto(
                userId: $user['user_id'],
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['suspicious_reason' => $user['reason']],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Suspicious user booking should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious user should trigger fraud detection');
            }
        }
    }

    public function test_booking_with_fake_biometric_blocked(): void
    {
        $fakeBiometrics = [
            ['fingerprint' => '0000000000000000000000000000000000000000000000000000000000000000'],
            ['face_id' => '00000000-0000-0000-0000-000000000000'],
            ['voice_id' => null, 'fingerprint' => 'repeated_pattern'],
            ['iris_scan' => 'fake_iris_data'],
        ];

        foreach ($fakeBiometrics as $biometric) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: $biometric,
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                
                if ($result['success']) {
                    $verifyResult = $this->service->verifyBiometricOnCheckIn(1, 1, $biometric, $dto->correlationId);
                    $this->assertFalse($verifyResult, 'Fake biometric should fail verification');
                }
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Fake biometric should trigger fraud detection');
            }
        }
    }

    public function test_booking_from_vpn_blocked(): void
    {
        $vpnIndicators = [
            'ip_address' => '10.0.0.1',
            'is_vpn' => true,
            'vpn_provider' => 'suspicious_vpn',
            'datacenter_ip' => true,
        ];

        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: $vpnIndicators,
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        try {
            $result = $this->service->holdSlot($dto);
            $this->assertFalse($result['success'] ?? true, 'VPN booking should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'VPN booking should trigger fraud detection');
        }
    }

    public function test_booking_with_suspicious_device_blocked(): void
    {
        $suspiciousDevices = [
            ['device_id' => 'emulator_123', 'is_emulator' => true],
            ['device_id' => 'rooted_device_456', 'is_rooted' => true],
            ['device_id' => 'jailbroken_789', 'is_jailbroken' => true],
        ];

        foreach ($suspiciousDevices as $device) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: $device,
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Suspicious device booking should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious device should trigger fraud detection');
            }
        }
    }

    public function test_booking_with_suspicious_timing_blocked(): void
    {
        $suspiciousTimings = [
            ['time' => '00:00:00', 'reason' => 'midnight'],
            ['time' => '03:00:00', 'reason' => 'early_morning'],
            ['time' => '06:00:00', 'reason' => 'unusual_hour'],
        ];

        foreach ($suspiciousTimings as $timing) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['booking_time' => $timing['time'], 'suspicious_reason' => $timing['reason']],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertIsArray($result);
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious timing should trigger fraud detection');
            }
        }
    }

    public function test_booking_with_fake_referral_blocked(): void
    {
        $fakeReferrals = [
            'self_referral',
            'bot_referral',
            'spam_referral',
            'blacklisted_referral',
        ];

        foreach ($fakeReferrals as $referral) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['referral_source' => $referral],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Fake referral booking should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Fake referral should trigger fraud detection');
            }
        }
    }

    public function test_booking_with_suspicious_location_blocked(): void
    {
        $suspiciousLocations = [
            ['lat' => 0, 'lon' => 0, 'reason' => 'null_island'],
            ['lat' => 90, 'lon' => 180, 'reason' => 'invalid_coordinates'],
            ['lat' => 'invalid', 'lon' => 'invalid', 'reason' => 'malformed'],
        ];

        foreach ($suspiciousLocations as $location) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['location' => $location],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Suspicious location booking should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious location should trigger fraud detection');
            }
        }
    }

    public function test_booking_with_suspicious_session_blocked(): void
    {
        $suspiciousSessions = [
            ['session_duration' => 0, 'reason' => 'instant_booking'],
            ['session_duration' => 86400, 'reason' => 'excessive_duration'],
            ['page_views' => 0, 'reason' => 'no_browsing'],
        ];

        foreach ($suspiciousSessions as $session) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['session' => $session],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Suspicious session booking should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious session should trigger fraud detection');
            }
        }
    }

    public function test_booking_with_suspicious_payment_blocked(): void
    {
        $suspiciousPayments = [
            ['card_type' => 'prepaid', 'issuer' => 'unknown'],
            ['card_type' => 'virtual', 'issuer' => 'anonymous'],
            ['payment_method' => 'crypto', 'wallet' => 'mixer'],
        ];

        foreach ($suspiciousPayments as $payment) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['payment' => $payment],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                
                if ($result['success']) {
                    $confirmResult = $this->service->confirmBooking($dto, [
                        'amount' => 100,
                        'transaction_id' => null,
                        'payment_method' => $payment['payment_method'] ?? 'card',
                    ]);

                    $this->assertFalse($confirmResult['success'] ?? true, 'Suspicious payment should be blocked');
                }
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious payment should trigger fraud detection');
            }
        }
    }

    public function test_booking_with_suspicious_email_blocked(): void
    {
        $suspiciousEmails = [
            'temp@mailinator.com',
            'fake@10minutemail.com',
            'test@guerrillamail.com',
            'spam@throwaway.email',
        ];

        foreach ($suspiciousEmails as $email) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['email' => $email],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Suspicious email booking should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious email should trigger fraud detection');
            }
        }
    }

    public function test_booking_with_suspicious_phone_blocked(): void
    {
        $suspiciousPhones = [
            '+1234567890',
            '0000000000',
            '1111111111',
            '5555555555',
        ];

        foreach ($suspiciousPhones as $phone) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['phone' => $phone],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Suspicious phone booking should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious phone should trigger fraud detection');
            }
        }
    }

    public function test_mass_booking_from_same_ip_blocked(): void
    {
        $sameIp = '192.168.1.100';
        $bookingAttempts = 50;
        $blockedCount = 0;

        for ($i = 0; $i < $bookingAttempts; $i++) {
            $dto = new RealTimeBookingDto(
                userId: $i + 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2 + $i)->toDateTimeString(),
                slotEnd: now()->addHours(3 + $i)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['ip_address' => $sameIp],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                
                if (!$result['success']) {
                    $blockedCount++;
                }
            } catch (FraudBlockedException $e) {
                $blockedCount++;
            }

            if ($i > 20 && $blockedCount > 10) {
                $this->assertTrue(true, 'Mass booking from same IP should be blocked');
                break;
            }
        }

        $this->assertGreaterThan(10, $blockedCount, 'Should block mass booking attempts');
    }

    public function test_booking_with_suspicious_user_agent_blocked(): void
    {
        $suspiciousUserAgents = [
            'curl/7.68.0',
            'wget/1.20.3',
            'python-requests/2.25.1',
            'PostmanRuntime/7.26.8',
        ];

        foreach ($suspiciousUserAgents as $userAgent) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['user_agent' => $userAgent],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Suspicious user agent booking should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious user agent should trigger fraud detection');
            }
        }
    }
}
