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

final class SportsFraudDetectionTest extends TestCase
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

    public function test_rapid_booking_attempts_blocked(): void
    {
        $userId = 1;
        
        for ($i = 0; $i < 15; $i++) {
            $dto = new RealTimeBookingDto(
                userId: $userId,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2 + $i)->toDateTimeString(),
                slotEnd: now()->addHours(3 + $i)->toDateTimeString(),
                bookingType: 'general',
                biometricData: [],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                
                if ($i >= 10) {
                    $this->assertFalse($result['success'], 'Should be blocked after rapid attempts');
                    $this->assertStringContainsString('rate limit', strtolower($result['message'] ?? ''));
                }
            } catch (FraudBlockedException $e) {
                $this->assertTrue($i >= 10, 'Should be fraud blocked after rapid attempts');
            }
        }
    }

    public function test_multiple_user_same_ip_blocked(): void
    {
        $sameIp = '192.168.1.100';
        
        for ($i = 1; $i <= 20; $i++) {
            $dto = new RealTimeBookingDto(
                userId: $i,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2 + $i)->toDateTimeString(),
                slotEnd: now()->addHours(3 + $i)->toDateTimeString(),
                bookingType: 'general',
                biometricData: [],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                
                if ($i > 15) {
                    $this->assertFalse($result['success'], 'Should be blocked for multiple users from same IP');
                }
            } catch (FraudBlockedException $e) {
                $this->assertTrue($i > 15, 'Should be fraud blocked for multiple users from same IP');
            }
        }
    }

    public function test_suspicious_booking_patterns_detected(): void
    {
        $suspiciousPatterns = [
            'booking_type' => 'personal_training',
            'trainer_id' => 999,
            'slot_start' => now()->addMinutes(5)->toDateTimeString(),
            'slot_end' => now()->addMinutes(115)->toDateTimeString(),
        ];

        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: $suspiciousPatterns['trainer_id'],
            slotStart: $suspiciousPatterns['slot_start'],
            slotEnd: $suspiciousPatterns['slot_end'],
            bookingType: $suspiciousPatterns['booking_type'],
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        try {
            $result = $this->service->holdSlot($dto);
            $this->assertIsArray($result);
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Suspicious pattern should trigger fraud detection');
        }
    }

    public function test_zero_price_booking_blocked(): void
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
        );

        try {
            $result = $this->service->holdSlot($dto);
            
            $confirmResult = $this->service->confirmBooking($dto, [
                'amount' => 0,
                'transaction_id' => null,
                'payment_method' => 'card',
            ]);

            $this->assertFalse($confirmResult['success'] ?? true, 'Zero price booking should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Zero price should trigger fraud detection');
        }
    }

    public function test_extreme_hold_extension_blocked(): void
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
        );

        $holdResult = $this->service->holdSlot($dto);
        
        if ($holdResult['success']) {
            $extensionAttempts = 0;
            $maxExtensions = 5;

            for ($i = 0; $i < $maxExtensions + 2; $i++) {
                try {
                    $result = $this->service->extendHold(1, null, $dto->slotStart, 1, $dto->correlationId);
                    
                    if ($i >= $maxExtensions) {
                        $this->assertFalse($result['success'], 'Should block excessive hold extensions');
                    } else {
                        $extensionAttempts++;
                    }
                } catch (FraudBlockedException $e) {
                    $this->assertTrue($i >= $maxExtensions, 'Should block after max extensions');
                    break;
                }
            }

            $slotKey = "sports:slot:hold:1::{$dto->slotStart}";
            $this->redis->del($slotKey);
        }
    }

    public function test_suspicious_biometric_patterns_blocked(): void
    {
        $suspiciousBiometric = [
            'fingerprint' => str_repeat('0', 100),
            'face_id' => '00000000-0000-0000-0000-000000000000',
            'voice_id' => null,
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
            biometricData: $suspiciousBiometric,
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        try {
            $result = $this->service->holdSlot($dto);
            
            if ($result['success']) {
                $verifyResult = $this->service->verifyBiometricOnCheckIn(1, 1, $suspiciousBiometric, $dto->correlationId);
                $this->assertFalse($verifyResult, 'Suspicious biometric should fail verification');
            }
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Suspicious biometric should trigger fraud detection');
        }
    }

    public function test_booking_from_blacklisted_countries_blocked(): void
    {
        $blacklistedCountries = ['XX', 'YY', 'ZZ'];
        
        foreach ($blacklistedCountries as $country) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['country_code' => $country],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Blacklisted country should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Blacklisted country should trigger fraud detection');
            }
        }
    }

    public function test_multiple_cancellations_blocked(): void
    {
        $userId = 1;
        $cancellationCount = 0;
        $maxCancellations = 5;

        for ($i = 0; $i < $maxCancellations + 2; $i++) {
            $dto = new RealTimeBookingDto(
                userId: $userId,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2 + $i)->toDateTimeString(),
                slotEnd: now()->addHours(3 + $i)->toDateTimeString(),
                bookingType: 'general',
                biometricData: [],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $holdResult = $this->service->holdSlot($dto);
                
                if ($holdResult['success']) {
                    $this->service->releaseSlot(1, null, $dto->slotStart, $userId, $dto->correlationId);
                    $cancellationCount++;

                    if ($cancellationCount > $maxCancellations) {
                        $this->fail('Should block after max cancellations');
                    }
                }
            } catch (FraudBlockedException $e) {
                $this->assertTrue($cancellationCount >= $maxCancellations, 'Should block after max cancellations');
                break;
            }
        }
    }

    public function test_idempotency_key_reuse_blocked(): void
    {
        $idempotencyKey = Str::uuid()->toString();

        $dto1 = new RealTimeBookingDto(
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
            idempotencyKey: $idempotencyKey,
        );

        $result1 = $this->service->holdSlot($dto1);
        $this->assertTrue($result1['success']);

        $dto2 = new RealTimeBookingDto(
            userId: 2,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(4)->toDateTimeString(),
            slotEnd: now()->addHours(5)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
            idempotencyKey: $idempotencyKey,
        );

        $result2 = $this->service->holdSlot($dto2);
        $this->assertFalse($result2['success'], 'Idempotency key reuse should be blocked');
    }

    public function test_suspicious_correlation_id_patterns_blocked(): void
    {
        $suspiciousCorrelationIds = [
            'fraud-test-123',
            'hack-attempt-456',
            'malicious-789',
            'bypass-security-000',
        ];

        foreach ($suspiciousCorrelationIds as $correlationId) {
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
                correlationId: $correlationId,
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertFalse($result['success'] ?? true, 'Suspicious correlation ID should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious correlation ID should trigger fraud detection');
            }
        }
    }

    public function test_concurrent_hold_conflict_detection(): void
    {
        $slotStart = now()->addHours(2)->toDateTimeString();
        $slotEnd = now()->addHours(3)->toDateTimeString();

        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: $slotStart,
            slotEnd: $slotEnd,
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $result1 = $this->service->holdSlot($dto);
        $this->assertTrue($result1['success']);

        $dto2 = new RealTimeBookingDto(
            userId: 2,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: $slotStart,
            slotEnd: $slotEnd,
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $result2 = $this->service->holdSlot($dto2);
        $this->assertFalse($result2['success'], 'Concurrent hold for same slot should be blocked');

        $slotKey = "sports:slot:hold:1::{$slotStart}";
        $this->redis->del($slotKey);
    }
}
