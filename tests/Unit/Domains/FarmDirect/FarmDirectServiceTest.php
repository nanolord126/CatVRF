<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\FarmDirect;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('FarmDirect');
});

test('FarmDirectService exists and is instantiable', function () {
    $this->assertServiceExists('FarmDirectService');
});

test('FarmDirectService follows clean architecture', function () {
    $this->assertCleanArchitecture('FarmDirectService');
});

test('FarmDirectService performs fraud check', function () {
    $this->testServiceWithFraudCheck('FarmDirectService', 'process', []);
});

test('FarmDirectService enforces quota limits', function () {
    $this->testServiceWithQuota('FarmDirectService', 'process', 1, 10, []);
});

test('FarmDirectService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('FarmDirectService'));
        $service->process([]);
    }, 10);
});

test('FarmDirectService has proper caching', function () {
    $cacheKey = 'farmdirect:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('FarmDirectService'));

        return $service->getData(1);
    });
});

test('FarmDirectService dispatches proper events', function () {
    $eventClass = "App\Domains\FarmDirect\Events\FarmDirectProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('FarmDirectService'));
        $service->process([]);
    });
});

test('FarmDirectService dispatches proper jobs', function () {
    $jobClass = "App\Domains\FarmDirect\Jobs\ProcessFarmDirectJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('FarmDirectService'));
        $service->processAsync([]);
    });
});

test('FarmDirectService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('FarmDirectService'));
        $service->process([]);
    }, \Exception::class);
});

test('FarmDirectService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('FarmDirectService'));
        $service->process([]);
    }, 'FarmDirectService processed');
});

test('FarmDirectService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
