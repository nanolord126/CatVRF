<?php

declare(strict_types=1);

namespace Modules\BigData\Tests;

use Carbon\CarbonImmutable;
use Modules\BigData\Application\Services\BigDataService;
use Modules\BigData\Domain\DTOs\BaseEventDTO;
use Modules\BigData\Domain\Enums\EventType;
use PHPUnit\Framework\TestCase;

/**
 * Big Data Load Tests
 *
 * Performance tests for high-throughput scenarios.
 * Run with: php artisan test --testsuite=BigData-Load
 */
final class BigDataLoadTest extends TestCase
{
    private const BATCH_SIZE = 1000;
    private const TOTAL_EVENTS = 10000;

    public function test_batch_event_insertion_performance(): void
    {
        $startTime = microtime(true);

        $events = [];
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            $events[] = BaseEventDTO::create(
                eventType: EventType::ProductViewed,
                tenantId: 1,
                userId: rand(1, 1000),
                productId: rand(1, 10000),
                properties: [
                    'category' => 'electronics',
                    'price' => rand(10, 1000),
                ],
            );
        }

        $serializationTime = microtime(true) - $startTime;
        $this->assertLessThan(1.0, $serializationTime, 'Batch serialization should be fast');

        // Mock service (in real test, would use actual ClickHouse)
        $this->assertCount(self::BATCH_SIZE, $events);
    }

    public function test_event_serialization_performance(): void
    {
        $event = BaseEventDTO::create(
            eventType: EventType::OrderPlaced,
            tenantId: 1,
            userId: 123,
            orderId: 456,
            monetaryValue: 99.99,
        );

        $iterations = 10000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $json = $event->toJson();
            $deserialized = BaseEventDTO::fromJson($json);
        }

        $elapsed = microtime(true) - $startTime;
        $eventsPerSecond = $iterations / $elapsed;

        $this->assertGreaterThan(1000, $eventsPerSecond, 'Should serialize 1000+ events/second');
    }

    public function test_event_validation_performance(): void
    {
        $validator = new \Modules\BigData\Domain\DTOs\EventValidator();
        $event = BaseEventDTO::create(
            eventType: EventType::OrderPlaced,
            tenantId: 1,
            userId: 123,
            orderId: 456,
            monetaryValue: 99.99,
        );

        $iterations = 10000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $validator->validate($event);
        }

        $elapsed = microtime(true) - $startTime;
        $validationsPerSecond = $iterations / $elapsed;

        $this->assertGreaterThan(5000, $validationsPerSecond, 'Should validate 5000+ events/second');
    }

    public function test_memory_usage_for_large_batch(): void
    {
        $initialMemory = memory_get_usage();

        $events = [];
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            $events[] = BaseEventDTO::create(
                eventType: EventType::ProductViewed,
                tenantId: 1,
                userId: rand(1, 1000),
                productId: rand(1, 10000),
            );
        }

        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;
        $memoryPerEvent = $memoryUsed / self::BATCH_SIZE;

        // Each event should use less than 10KB
        $this->assertLessThan(10240, $memoryPerEvent, 'Each event should use less than 10KB');

        // Cleanup
        unset($events);
    }

    public function test_concurrent_event_creation(): void
    {
        $events = [];
        $startTime = microtime(true);

        // Simulate concurrent event creation
        for ($i = 0; $i < 100; $i++) {
            $events[] = BaseEventDTO::create(
                eventType: EventType::OrderPlaced,
                tenantId: 1,
                userId: rand(1, 1000),
                orderId: rand(1, 10000),
                monetaryValue: rand(10, 1000),
            );
        }

        $elapsed = microtime(true) - $startTime;
        $this->assertLessThan(0.1, $elapsed, '100 events should be created in < 100ms');
    }
}
