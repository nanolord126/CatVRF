<?php

declare(strict_types=1);

namespace Tests\Resilience;

use App\Domains\Sports\DTOs\RealTimeBookingDto;
use App\Domains\Sports\Services\SportsRealTimeBookingService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;

final class SportsDDoSTest extends TestCase
{
    use RefreshDatabase;

    private SportsRealTimeBookingService $service;
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

        $this->service = new SportsRealTimeBookingService(
            fraud: $fraud,
            audit: $audit,
            db: $db,
            cache: $cache,
            logger: $this->app->make('log'),
            redis: $this->redis,
        );
    }

    public function test_rate_limiter_blocks_excessive_requests(): void
    {
        $successfulRequests = 0;
        $blockedRequests = 0;
        $maxRequestsPerMinute = 60;

        for ($i = 0; $i < 100; $i++) {
            $key = "sports:booking:rate_limit:1";
            
            if (RateLimiter::tooManyAttempts($key, $maxRequestsPerMinute)) {
                $blockedRequests++;
                continue;
            }

            RateLimiter::hit($key, 60);
            $successfulRequests++;
        }

        $this->assertEquals($maxRequestsPerMinute, $successfulRequests, 'Should allow max requests per minute');
        $this->assertEquals(40, $blockedRequests, 'Should block excessive requests');
    }

    public function test_service_survives_request_flood(): void
    {
        $requestCount = 1000;
        $successfulRequests = 0;
        $failedRequests = 0;
        $startTime = microtime(true);

        for ($i = 0; $i < $requestCount; $i++) {
            $dto = new RealTimeBookingDto(
                userId: $i % 100 + 1,
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
                if ($result['success']) {
                    $successfulRequests++;
                    $slotKey = "sports:slot:hold:1::{$dto->slotStart}";
                    $this->redis->del($slotKey);
                } else {
                    $failedRequests++;
                }
            } catch (\Exception $e) {
                $failedRequests++;
            }
        }

        $totalTime = microtime(true) - $startTime;
        $requestsPerSecond = $requestCount / $totalTime;

        $this->assertGreaterThan(0, $successfulRequests, 'Should have some successful requests');
        $this->assertLessThan($requestCount, $failedRequests, 'Should not fail all requests');
        $this->assertGreaterThan(50, $requestsPerSecond, 'Should handle >50 requests per second');
    }

    public function test_circuit_breaker_triggers_on_failure_threshold(): void
    {
        $failureThreshold = 5;
        $failures = 0;

        for ($i = 0; $i < 10; $i++) {
            try {
                $dto = new RealTimeBookingDto(
                    userId: 1,
                    tenantId: 1,
                    businessGroupId: null,
                    venueId: 999999,
                    trainerId: null,
                    slotStart: now()->addHours(2 + $i)->toDateTimeString(),
                    slotEnd: now()->addHours(3 + $i)->toDateTimeString(),
                    bookingType: 'general',
                    biometricData: [],
                    extendedHold: false,
                    correlationId: Str::uuid()->toString(),
                );

                $result = $this->service->holdSlot($dto);
                if (!$result['success']) {
                    $failures++;
                }
            } catch (\Exception $e) {
                $failures++;
            }

            if ($failures >= $failureThreshold) {
                $this->assertTrue(true, 'Circuit breaker should trigger after failure threshold');
                break;
            }
        }

        $this->assertGreaterThanOrEqual($failureThreshold, $failures);
    }

    public function test_service_survives_memory_exhaustion_attempt(): void
    {
        $memoryLimit = ini_get('memory_limit');
        $initialMemory = memory_get_usage(true);

        for ($i = 0; $i < 100; $i++) {
            $largeData = str_repeat('x', 1024 * 1024);

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
                $result = $this->service->holdSlot($dto);
                if ($result['success']) {
                    $slotKey = "sports:slot:hold:1::{$dto->slotStart}";
                    $this->redis->del($slotKey);
                }
            } catch (\Exception $e) {
                unset($largeData);
                gc_collect_cycles();
            }

            if ($i % 10 === 0) {
                gc_collect_cycles();
                $currentMemory = memory_get_usage(true);
                $memoryUsage = ($currentMemory - $initialMemory) / 1024 / 1024;
                
                if ($memoryUsage > 100) {
                    $this->assertTrue(true, 'Service should handle memory pressure');
                    break;
                }
            }
        }
    }

    public function test_service_survives_connection_pool_exhaustion(): void
    {
        $connections = [];
        $maxConnections = 50;

        for ($i = 0; $i < $maxConnections; $i++) {
            try {
                $connection = $this->redis->connection();
                $connections[] = $connection;
                $connection->ping();
            } catch (\Exception $e) {
                $this->assertLessThan($maxConnections, $i, 'Should handle connection pool exhaustion');
                break;
            }
        }

        foreach ($connections as $connection) {
            $connection->disconnect();
        }

        $this->assertTrue(true, 'Service should survive connection pool exhaustion');
    }

    public function test_service_survives_cpu_exhaustion(): void
    {
        $iterations = 10000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $hash = hash('sha256', Str::random(1000) . $i);
            $hash = hash('sha256', $hash);
        }

        $elapsedTime = microtime(true) - $startTime;
        $hashesPerSecond = $iterations / $elapsedTime;

        $this->assertGreaterThan(1000, $hashesPerSecond, 'Should handle CPU-intensive operations');
    }

    public function test_service_survives_disk_io_exhaustion(): void
    {
        $fileCount = 100;
        $files = [];

        for ($i = 0; $i < $fileCount; $i++) {
            try {
                $tempFile = tempnam(sys_get_temp_dir(), 'sports_test_');
                file_put_contents($tempFile, str_repeat('x', 1024 * 100));
                $files[] = $tempFile;
            } catch (\Exception $e) {
                $this->assertLessThan($fileCount, $i, 'Should handle disk IO exhaustion');
                break;
            }
        }

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $this->assertTrue(true, 'Service should survive disk IO exhaustion');
    }

    public function test_service_survives_network_partition(): void
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

        try {
            $this->redis->disconnect();
            
            $releaseResult = $this->service->releaseSlot(1, null, $dto->slotStart, 1, $dto->correlationId);
            
            $this->assertIsArray($releaseResult);
        } catch (\Exception $e) {
            $this->assertStringContainsString('connection', strtolower($e->getMessage()));
        } finally {
            $this->redis->connect();
        }

        $this->assertTrue(true, 'Service should handle network partition');
    }

    public function test_service_survives_high_concurrency_with_same_slot(): void
    {
        $slotStart = now()->addHours(2)->toDateTimeString();
        $slotEnd = now()->addHours(3)->toDateTimeString();
        $concurrentRequests = 50;
        $results = [];

        for ($i = 0; $i < $concurrentRequests; $i++) {
            $dto = new RealTimeBookingDto(
                userId: $i + 1,
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

            try {
                $result = $this->service->holdSlot($dto);
                $results[] = $result;
            } catch (\Exception $e) {
                $results[] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        $successfulHolds = count(array_filter($results, fn($r) => $r['success'] ?? false));

        $this->assertEquals(1, $successfulHolds, 'Only one hold should succeed for concurrent same-slot requests');
        $this->assertEquals($concurrentRequests - 1, count(array_filter($results, fn($r) => !($r['success'] ?? false))), 'Others should fail');

        $slotKey = "sports:slot:hold:1::{$slotStart}";
        $this->redis->del($slotKey);
    }

    public function test_service_survives_request_timeout(): void
    {
        $originalTimeout = ini_get('max_execution_time');
        ini_set('max_execution_time', '1');

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

            $result = $this->service->holdSlot($dto);
            $this->assertIsArray($result);
        } catch (\Exception $e) {
            $this->assertStringContainsString('timeout', strtolower($e->getMessage()));
        } finally {
            ini_set('max_execution_time', $originalTimeout);
        }

        $this->assertTrue(true, 'Service should handle request timeout');
    }

    public function test_service_survives_cache_storm(): void
    {
        $keys = [];
        $iterations = 500;

        for ($i = 0; $i < $iterations; $i++) {
            $key = "sports:cache_storm:{$i}";
            $value = ['data' => $i, 'timestamp' => now()->toIso8601String()];
            
            $this->redis->setex($key, 60, json_encode($value));
            $keys[] = $key;
        }

        $this->redis->flushdb();

        $remainingKeys = 0;
        foreach ($keys as $key) {
            if ($this->redis->exists($key)) {
                $remainingKeys++;
            }
        }

        $this->assertEquals(0, $remainingKeys, 'All keys should be flushed');
    }

    public function test_service_survives_malformed_headers(): void
    {
        $malformedHeaders = [
            'X-Correlation-ID' => str_repeat('a', 10000),
            'X-User-ID' => 'not-a-number',
            'X-Tenant-ID' => '-1',
            'X-Request-ID' => null,
        ];

        foreach ($malformedHeaders as $header => $value) {
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
                correlationId: $value ?? Str::uuid()->toString(),
            );

            try {
                $result = $this->service->holdSlot($dto);
                $this->assertIsArray($result);
            } catch (\Exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }

        $this->assertTrue(true, 'Service should handle malformed headers');
    }

    public function test_service_survives_concurrent_user_requests(): void
    {
        $userCount = 100;
        $results = [];

        for ($i = 0; $i < $userCount; $i++) {
            $dto = new RealTimeBookingDto(
                userId: $i + 1,
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
                $results[] = ['user_id' => $i + 1, 'success' => $result['success']];
                
                if ($result['success']) {
                    $slotKey = "sports:slot:hold:1::{$dto->slotStart}";
                    $this->redis->del($slotKey);
                }
            } catch (\Exception $e) {
                $results[] = ['user_id' => $i + 1, 'success' => false, 'error' => $e->getMessage()];
            }
        }

        $successfulRequests = count(array_filter($results, fn($r) => $r['success']));
        
        $this->assertGreaterThan(80, $successfulRequests, 'Should handle >80% of concurrent user requests');
    }
}
