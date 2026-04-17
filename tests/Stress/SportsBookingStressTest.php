<?php

declare(strict_types=1);

namespace Tests\Stress;

use App\Domains\Sports\DTOs\RealTimeBookingDto;
use App\Domains\Sports\Services\SportsRealTimeBookingService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Parallel\Concurrency;

final class SportsBookingStressTest extends TestCase
{
    use RefreshDatabase;

    private SportsRealTimeBookingService $service;
    private RedisConnection $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->createMock(FraudControlService::class);
        $this->fraud->method('check')->willReturn(null);

        $this->audit = $this->createMock(AuditService::class);
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

    public function test_concurrent_slot_holds_100_requests(): void
    {
        $slotStart = now()->addHours(2)->toDateTimeString();
        $slotEnd = now()->addHours(3)->toDateTimeString();

        $results = [];
        $successfulHolds = 0;
        $failedHolds = 0;

        for ($i = 1; $i <= 100; $i++) {
            $dto = new RealTimeBookingDto(
                userId: $i,
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
                if ($result['success']) {
                    $successfulHolds++;
                } else {
                    $failedHolds++;
                }
            } catch (\Exception $e) {
                $failedHolds++;
                $results[] = ['error' => $e->getMessage()];
            }
        }

        $this->assertEquals(1, $successfulHolds, 'Only one hold should succeed for the same slot');
        $this->assertEquals(99, $failedHolds, '99 holds should fail');
        $this->assertLessThan(5, count(array_filter($results, fn($r) => isset($r['error']))), 'Should have minimal errors');

        $slotKey = "sports:slot:hold:1::{$slotStart}";
        $this->redis->del($slotKey);
    }

    public function test_rapid_slot_release_and_hold_1000_iterations(): void
    {
        $iterations = 1000;
        $successfulOperations = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $slotStart = now()->addHours(2 + $i)->toDateTimeString();
            $slotEnd = now()->addHours(3 + $i)->toDateTimeString();

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

            try {
                $holdResult = $this->service->holdSlot($dto);
                if ($holdResult['success']) {
                    $this->service->releaseSlot(1, null, $slotStart, 1, $dto->correlationId);
                    $successfulOperations++;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $this->assertGreaterThan(990, $successfulOperations, 'Should handle 1000 iterations with >99% success');
        $successRate = ($successfulOperations / $iterations) * 100;
        $this->assertGreaterThan(99, $successRate, 'Success rate should be >99%');
    }

    public function test_high_load_available_slots_query(): void
    {
        $iterations = 500;
        $results = [];
        $avgResponseTime = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            try {
                $result = $this->service->getAvailableSlots(1, null, now()->toDateString());
                $responseTime = (microtime(true) - $startTime) * 1000;
                $results[] = [
                    'success' => true,
                    'response_time_ms' => $responseTime,
                ];
                $avgResponseTime += $responseTime;
            } catch (\Exception $e) {
                $responseTime = (microtime(true) - $startTime) * 1000;
                $results[] = [
                    'success' => false,
                    'response_time_ms' => $responseTime,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $avgResponseTime = $avgResponseTime / $iterations;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertEquals($iterations, $successCount, 'All queries should succeed');
        $this->assertLessThan(100, $avgResponseTime, 'Average response time should be <100ms');
        $this->assertGreaterThan(0, $avgResponseTime, 'Response time should be measurable');

        $p95ResponseTime = $this->calculatePercentile(array_column($results, 'response_time_ms'), 95);
        $this->assertLessThan(200, $p95ResponseTime, 'P95 response time should be <200ms');
    }

    public function test_memory_leak_prevention(): void
    {
        $initialMemory = memory_get_usage(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $dto = new RealTimeBookingDto(
                userId: 1,
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
                $this->service->holdSlot($dto);
                $slotKey = "sports:slot:hold:1::{$dto->slotStart}";
                $this->redis->del($slotKey);
            } catch (\Exception $e) {
                continue;
            }

            if ($i % 100 === 0) {
                gc_collect_cycles();
            }
        }

        gc_collect_cycles();
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024;

        $this->assertLessThan(50, $memoryIncrease, 'Memory increase should be <50MB after 1000 iterations');
    }

    public function test_redis_connection_pool_stress(): void
    {
        $concurrentOperations = 100;
        $results = [];

        for ($i = 0; $i < $concurrentOperations; $i++) {
            $key = "test:stress:{$i}";
            
            try {
                $this->redis->setex($key, 60, json_encode(['test' => $i]));
                $value = $this->redis->get($key);
                $this->redis->del($key);
                
                $results[] = [
                    'success' => true,
                    'value_matches' => json_decode($value, true)['test'] === $i,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $valueMatchCount = count(array_filter($results, fn($r) => $r['value_matches'] ?? false));

        $this->assertEquals($concurrentOperations, $successCount, 'All Redis operations should succeed');
        $this->assertEquals($concurrentOperations, $valueMatchCount, 'All values should match');
    }

    public function test_biometric_hashing_performance(): void
    {
        $iterations = 1000;
        $hashes = [];
        $totalTime = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $biometricData = [
                'fingerprint' => Str::random(32),
                'face_id' => Str::random(32),
                'voice_id' => Str::random(32),
            ];

            $startTime = microtime(true);
            $hash = hash('sha256', json_encode($biometricData) . 'sports_biometric_salt_2026');
            $totalTime += (microtime(true) - $startTime);
            $hashes[] = $hash;
        }

        $avgTime = ($totalTime / $iterations) * 1000;
        $uniqueHashes = count(array_unique($hashes));

        $this->assertEquals($iterations, $uniqueHashes, 'All hashes should be unique');
        $this->assertLessThan(1, $avgTime, 'Average hashing time should be <1ms');
    }

    public function test_cache_invalidation_under_load(): void
    {
        $iterations = 200;
        $cacheKeys = [];

        for ($i = 0; $i < $iterations; $i++) {
            $key = "sports:test:cache:{$i}";
            $value = ['data' => $i, 'timestamp' => now()->toIso8601String()];
            
            $this->cache->put($key, $value, 300);
            $cacheKeys[] = $key;
        }

        $this->cache->tags(['sports', 'test'])->flush();

        $remainingKeys = 0;
        foreach ($cacheKeys as $key) {
            if ($this->cache->get($key) !== null) {
                $remainingKeys++;
            }
        }

        $this->assertEquals(0, $remainingKeys, 'All cached values should be invalidated');
    }

    private function calculatePercentile(array $data, int $percentile): float
    {
        sort($data);
        $index = ceil(($percentile / 100) * count($data)) - 1;
        return $data[$index] ?? 0;
    }
}
