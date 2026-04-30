<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Collectibles;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Collectibles');
});

test('CollectiblesService exists and is instantiable', function () {
    $this->assertServiceExists('CollectiblesService');
});

test('CollectiblesService follows clean architecture', function () {
    $this->assertCleanArchitecture('CollectiblesService');
});

test('CollectiblesService performs fraud check', function () {
    $this->testServiceWithFraudCheck('CollectiblesService', 'process', []);
});

test('CollectiblesService enforces quota limits', function () {
    $this->testServiceWithQuota('CollectiblesService', 'process', 1, 10, []);
});

test('CollectiblesService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('CollectiblesService'));
        $service->process([]);
    }, 10);
});

test('CollectiblesService has proper caching', function () {
    $cacheKey = 'collectibles:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('CollectiblesService'));

        return $service->getData(1);
    });
});

test('CollectiblesService dispatches proper events', function () {
    $eventClass = "App\Domains\Collectibles\Events\CollectiblesProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('CollectiblesService'));
        $service->process([]);
    });
});

test('CollectiblesService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Collectibles\Jobs\ProcessCollectiblesJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('CollectiblesService'));
        $service->processAsync([]);
    });
});

test('CollectiblesService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('CollectiblesService'));
        $service->process([]);
    }, \Exception::class);
});

test('CollectiblesService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('CollectiblesService'));
        $service->process([]);
    }, 'CollectiblesService processed');
});

test('CollectiblesService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
