<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\ToysAndGames;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('ToysAndGames');
});

test('ToysAndGamesService exists and is instantiable', function () {
    $this->assertServiceExists('ToysAndGamesService');
});

test('ToysAndGamesService follows clean architecture', function () {
    $this->assertCleanArchitecture('ToysAndGamesService');
});

test('ToysAndGamesService performs fraud check', function () {
    $this->testServiceWithFraudCheck('ToysAndGamesService', 'process', []);
});

test('ToysAndGamesService enforces quota limits', function () {
    $this->testServiceWithQuota('ToysAndGamesService', 'process', 1, 10, []);
});

test('ToysAndGamesService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('ToysAndGamesService'));
        $service->process([]);
    }, 10);
});

test('ToysAndGamesService has proper caching', function () {
    $cacheKey = 'toysandgames:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('ToysAndGamesService'));

        return $service->getData(1);
    });
});

test('ToysAndGamesService dispatches proper events', function () {
    $eventClass = "App\Domains\ToysAndGames\Events\ToysAndGamesProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('ToysAndGamesService'));
        $service->process([]);
    });
});

test('ToysAndGamesService dispatches proper jobs', function () {
    $jobClass = "App\Domains\ToysAndGames\Jobs\ProcessToysAndGamesJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('ToysAndGamesService'));
        $service->processAsync([]);
    });
});

test('ToysAndGamesService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('ToysAndGamesService'));
        $service->process([]);
    }, \Exception::class);
});

test('ToysAndGamesService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('ToysAndGamesService'));
        $service->process([]);
    }, 'ToysAndGamesService processed');
});

test('ToysAndGamesService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
