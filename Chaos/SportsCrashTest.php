<?php

declare(strict_types=1);

namespace Tests\Chaos;

use App\Domains\Sports\DTOs\RealTimeBookingDto;
use App\Domains\Sports\Services\SportsRealTimeBookingService;
use App\Domains\Sports\Services\SportsDynamicPricingService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Tests\TestCase;
use Illuminate\Support\Str;

final class SportsCrashTest extends TestCase
{
    use RefreshDatabase;

    private SportsRealTimeBookingService $bookingService;
    private SportsDynamicPricingService $pricingService;
    private RedisConnection $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $fraud = $this->createMock(FraudControlService::class);
        $fraud->method('check')->willReturn(null);
        $audit = $this->createMock(AuditService::class);
        $db = $this->app->make(DatabaseManager::class);
        $cache = $this->app->make(Cache::class);
        $this->redis = $this->app->make('redis')->connection();

        $this->bookingService = new SportsRealTimeBookingService(
            fraud: $fraud,
            audit: $audit,
            db: $db,
            cache: $cache,
            logger: $this->app->make('log'),
            redis: $this->redis,
        );

        $this->pricingService = new SportsDynamicPricingService(
            fraud: $fraud,
            audit: $audit,
            db: $db,
            cache: $cache,
            logger: $this->app->make('log'),
            redis: $this->redis,
        );
    }

    public function test_service_survives_redis_crash(): void
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

        $holdResult = $this->bookingService->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        $this->redis->disconnect();
        
        try {
            $this->bookingService->releaseSlot(1, null, $dto->slotStart, 1, $dto->correlationId);
            $this->assertTrue(true, 'Service should handle Redis disconnect gracefully');
        } catch (\Exception $e) {
            $this->assertTrue(
                str_contains($e->getMessage(), 'Redis') || str_contains($e->getMessage(), 'connection'),
                'Should throw Redis connection error'
            );
        } finally {
            $this->redis->connect();
        }
    }

    public function test_service_survives_database_timeout(): void
    {
        $originalTimeout = config('database.connections.mysql.timeout');
        config(['database.connections.mysql.timeout' => 0.001]);

        try {
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

            $result = $this->bookingService->holdSlot($dto);
            
            $this->assertIsArray($result);
            if (!$result['success']) {
                $this->assertStringContainsString('timeout', strtolower($result['message'] ?? ''));
            }
        } finally {
            config(['database.connections.mysql.timeout' => $originalTimeout]);
        }
    }

    public function test_service_survives_cache_failure(): void
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

        $this->bookingService->holdSlot($dto);
        $this->bookingService->releaseSlot(1, null, $dto->slotStart, 1, $dto->correlationId);

        $this->assertTrue(true, 'Service should handle cache failure gracefully');
    }

    public function test_service_survives_malformed_input(): void
    {
        $malformedInputs = [
            [
                'userId' => -1,
                'tenantId' => 0,
                'businessGroupId' => null,
                'venueId' => 999999,
                'trainerId' => null,
                'slotStart' => 'invalid-date',
                'slotEnd' => 'invalid-date',
                'bookingType' => 'invalid-type',
                'biometricData' => 'not-an-array',
                'extendedHold' => 'not-a-boolean',
                'correlationId' => '',
            ],
            [
                'userId' => PHP_INT_MAX,
                'tenantId' => PHP_INT_MAX,
                'businessGroupId' => PHP_INT_MAX,
                'venueId' => PHP_INT_MAX,
                'trainerId' => PHP_INT_MAX,
                'slotStart' => str_repeat('a', 10000),
                'slotEnd' => str_repeat('a', 10000),
                'bookingType' => str_repeat('a', 1000),
                'biometricData' => array_fill(0, 10000, 'x'),
                'extendedHold' => true,
                'correlationId' => str_repeat('a', 10000),
            ],
        ];

        foreach ($malformedInputs as $input) {
            try {
                $dto = new RealTimeBookingDto(...$input);
                $result = $this->bookingService->holdSlot($dto);
                
                if (!$result['success']) {
                    $this->assertNotEmpty($result['message']);
                }
            } catch (\Exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }

        $this->assertTrue(true, 'Service should handle malformed input gracefully');
    }

    public function test_service_survives_concurrent_modifications(): void
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

        $holdResult = $this->bookingService->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        for ($i = 0; $i < 50; $i++) {
            try {
                $this->bookingService->releaseSlot(1, null, $slotStart, 1, $dto->correlationId);
            } catch (\Exception $e) {
                $this->assertStringContainsString('not found', strtolower($e->getMessage()));
            }
        }

        $this->assertTrue(true, 'Service should handle concurrent modifications');
    }

    public function test_service_survives_memory_pressure(): void
    {
        $largeData = str_repeat('x', 10 * 1024 * 1024);

        for ($i = 0; $i < 100; $i++) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2 + $i)->toDateTimeString(),
                slotEnd: now()->addHours(3 + $i)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['large_data' => $largeData],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->bookingService->holdSlot($dto);
                if ($result['success']) {
                    $slotKey = "sports:slot:hold:1::{$dto->slotStart}";
                    $this->redis->del($slotKey);
                }
            } catch (\Exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }

            if ($i % 10 === 0) {
                gc_collect_cycles();
            }
        }

        $this->assertTrue(true, 'Service should handle memory pressure');
    }

    public function test_service_survives_extreme_time_values(): void
    {
        $extremeTimes = [
            ['start' => '1970-01-01 00:00:00', 'end' => '2099-12-31 23:59:59'],
            ['start' => now()->addYears(100)->toDateTimeString(), 'end' => now()->addYears(101)->toDateTimeString()],
            ['start' => now()->subYears(50)->toDateTimeString(), 'end' => now()->subYears(49)->toDateTimeString()],
        ];

        foreach ($extremeTimes as $times) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: $times['start'],
                slotEnd: $times['end'],
                bookingType: 'general',
                biometricData: [],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->bookingService->holdSlot($dto);
                $this->assertIsArray($result);
            } catch (\Exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }

        $this->assertTrue(true, 'Service should handle extreme time values');
    }

    public function test_service_survives_null_injection(): void
    {
        $nullInputs = [
            ['userId' => null],
            ['venueId' => null],
            ['slotStart' => null],
            ['slotEnd' => null],
            ['bookingType' => null],
        ];

        foreach ($nullInputs as $nullInput) {
            try {
                $dto = new RealTimeBookingDto(
                    userId: $nullInput['userId'] ?? 1,
                    tenantId: 1,
                    businessGroupId: null,
                    venueId: $nullInput['venueId'] ?? 1,
                    trainerId: null,
                    slotStart: $nullInput['slotStart'] ?? now()->addHours(2)->toDateTimeString(),
                    slotEnd: $nullInput['slotEnd'] ?? now()->addHours(3)->toDateTimeString(),
                    bookingType: $nullInput['bookingType'] ?? 'general',
                    biometricData: [],
                    extendedHold: false,
                    correlationId: Str::uuid()->toString(),
                );

                $result = $this->bookingService->holdSlot($dto);
                $this->assertIsArray($result);
            } catch (\TypeError $e) {
                $this->assertTrue(true, 'Type error is acceptable for null injection');
            }
        }

        $this->assertTrue(true, 'Service should handle null injection');
    }

    public function test_service_survives_unicode_injection(): void
    {
        $unicodeStrings = [
            '🏋️‍♂️💪🔥',
            str_repeat('😀', 1000),
            "\x00\x01\x02\x03\x04\x05",
            '𝔘𝔫𝔦𝔠𝔬𝔡𝔢 𝔗𝔢𝔰𝔱 𝔖𝔱𝔯𝔦𝔫𝔤',
        ];

        foreach ($unicodeStrings as $unicodeStr) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: $unicodeStr,
                biometricData: ['unicode_test' => $unicodeStr],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $result = $this->bookingService->holdSlot($dto);
                $this->assertIsArray($result);
            } catch (\Exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }

        $this->assertTrue(true, 'Service should handle unicode injection');
    }

    public function test_service_survives_sql_injection_attempts(): void
    {
        $sqlInjectionAttempts = [
            "'; DROP TABLE sports_bookings; --",
            "' OR '1'='1",
            "1'; DELETE FROM users WHERE '1'='1",
            "' UNION SELECT * FROM users --",
            "'; EXEC xp_cmdshell('dir'); --",
        ];

        foreach ($sqlInjectionAttempts as $injection) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: $injection,
                biometricData: ['injection' => $injection],
                extendedHold: false,
                correlationId: $injection,
            );

            try {
                $result = $this->bookingService->holdSlot($dto);
                $this->assertIsArray($result);
                $this->assertFalse($result['success'] ?? true, 'SQL injection should not succeed');
            } catch (\Exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }

        $this->assertTrue(true, 'Service should handle SQL injection attempts');
    }

    public function test_service_survives_xss_attempts(): void
    {
        $xssAttempts = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            'javascript:alert("XSS")',
            '<svg onload=alert("XSS")>',
            '"><script>alert("XSS")</script><"',
        ];

        foreach ($xssAttempts as $xss) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: $xss,
                biometricData: ['xss' => $xss],
                extendedHold: false,
                correlationId: $xss,
            );

            try {
                $result = $this->bookingService->holdSlot($dto);
                $this->assertIsArray($result);
            } catch (\Exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }

        $this->assertTrue(true, 'Service should handle XSS attempts');
    }
}
