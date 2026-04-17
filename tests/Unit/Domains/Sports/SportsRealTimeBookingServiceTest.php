<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Sports;

use App\Domains\Sports\DTOs\RealTimeBookingDto;
use App\Domains\Sports\Services\SportsRealTimeBookingService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SportsRealTimeBookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SportsRealTimeBookingService $service;
    private FraudControlService $fraud;
    private AuditService $audit;
    private DatabaseManager $db;
    private Cache $cache;
    private RedisConnection $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->createMock(FraudControlService::class);
        $this->audit = $this->createMock(AuditService::class);
        $this->db = $this->app->make(DatabaseManager::class);
        $this->cache = $this->app->make(Cache::class);
        $this->redis = $this->app->make('redis');

        $this->service = new SportsRealTimeBookingService(
            fraud: $this->fraud,
            audit: $this->audit,
            db: $this->db,
            cache: $this->cache,
            logger: $this->app->make('log'),
            redis: $this->redis,
        );
    }

    public function test_hold_slot_success(): void
    {
        $this->fraud->expects($this->once())
            ->method('check')
            ->with(
                $this->equalTo(1),
                $this->equalTo('slot_hold'),
                $this->equalTo(0),
                $this->equalTo('test-correlation-id')
            );

        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: 1,
            slotStart: now()->addHours(2)->toIso8601String(),
            slotEnd: now()->addHours(3)->toIso8601String(),
            bookingType: 'personal_training',
            biometricData: ['fingerprint' => 'test'],
            extendedHold: false,
            correlationId: 'test-correlation-id',
        );

        $result = $this->service->holdSlot($dto);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('hold_until', $result);
        $this->assertArrayHasKey('hold_id', $result);
        $this->assertTrue($result['biometric_verified']);

        $this->redis->del($result['hold_id']);
    }

    public function test_hold_slot_already_held_by_another_user(): void
    {
        $this->fraud->expects($this->exactly(2))
            ->method('check');

        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: 1,
            slotStart: now()->addHours(2)->toIso8601String(),
            slotEnd: now()->addHours(3)->toIso8601String(),
            bookingType: 'personal_training',
            biometricData: [],
            extendedHold: false,
            correlationId: 'test-correlation-id',
        );

        $this->service->holdSlot($dto);

        $dto2 = new RealTimeBookingDto(
            userId: 2,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: 1,
            slotStart: now()->addHours(2)->toIso8601String(),
            slotEnd: now()->addHours(3)->toIso8601String(),
            bookingType: 'personal_training',
            biometricData: [],
            extendedHold: false,
            correlationId: 'test-correlation-id-2',
        );

        $result = $this->service->holdSlot($dto2);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already held', $result['message']);

        $slotKey = "sports:slot:hold:1:1:" . now()->addHours(2)->toIso8601String();
        $this->redis->del($slotKey);
    }

    public function test_extend_hold_success(): void
    {
        $this->fraud->expects($this->exactly(2))
            ->method('check');

        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: 1,
            slotStart: now()->addHours(2)->toIso8601String(),
            slotEnd: now()->addHours(3)->toIso8601String(),
            bookingType: 'personal_training',
            biometricData: [],
            extendedHold: false,
            correlationId: 'test-correlation-id',
        );

        $this->service->holdSlot($dto);

        $slotKey = "sports:slot:hold:1:1:" . now()->addHours(2)->toIso8601String();
        $result = $this->service->extendHold(1, 1, now()->addHours(2)->toIso8601String(), 1, 'test-correlation-id-2');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('hold_until', $result);

        $this->redis->del($slotKey);
    }

    public function test_release_slot_success(): void
    {
        $this->fraud->expects($this->once())
            ->method('check');

        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toIso8601String(),
            slotEnd: now()->addHours(3)->toIso8601String(),
            bookingType: 'gym_access',
            biometricData: [],
            extendedHold: false,
            correlationId: 'test-correlation-id',
        );

        $this->service->holdSlot($dto);

        $slotKey = "sports:slot:hold:1::" . now()->addHours(2)->toIso8601String();
        $this->service->releaseSlot(1, null, now()->addHours(2)->toIso8601String(), 1, 'test-correlation-id-2');

        $this->assertFalse($this->redis->exists($slotKey));
    }

    public function test_get_available_slots_success(): void
    {
        $date = now()->toDateString();
        $result = $this->service->getAvailableSlots(1, null, $date);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('start', $result[0]);
        $this->assertArrayHasKey('end', $result[0]);
        $this->assertArrayHasKey('available', $result[0]);
    }

    public function test_verify_biometric_on_check_in_success(): void
    {
        $this->fraud->expects($this->once())
            ->method('check')
            ->with(
                $this->equalTo(1),
                $this->equalTo('biometric_check_in'),
                $this->equalTo(0),
                $this->equalTo('test-correlation-id')
            );

        $biometricData = ['fingerprint' => 'test123'];
        $result = $this->callPrivateMethod($this->service, 'hashBiometricData', [$biometricData]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_hold_slot_extended_hold(): void
    {
        $this->fraud->expects($this->once())
            ->method('check');

        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: 1,
            slotStart: now()->addHours(2)->toIso8601String(),
            slotEnd: now()->addHours(3)->toIso8601String(),
            bookingType: 'personal_training',
            biometricData: [],
            extendedHold: true,
            correlationId: 'test-correlation-id',
        );

        $result = $this->service->holdSlot($dto);

        $this->assertTrue($result['success']);
        $slotKey = "sports:slot:hold:1:1:" . now()->addHours(2)->toIso8601String();
        $holdData = json_decode($this->redis->get($slotKey), true);
        
        $this->assertTrue($holdData['extended']);

        $this->redis->del($slotKey);
    }

    private function callPrivateMethod(object $object, string $methodName, array $parameters): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
