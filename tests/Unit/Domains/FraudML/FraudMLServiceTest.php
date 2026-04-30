<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('FraudML');
});

test('FraudMLService exists and is instantiable', function () {
    $this->assertServiceExists('FraudMLService');
});

test('FraudMLService follows clean architecture', function () {
    $this->assertCleanArchitecture('FraudMLService');
});

test('FraudMLService performs fraud check', function () {
    $this->testServiceWithFraudCheck('FraudMLService', 'process', []);
});

test('FraudMLService enforces quota limits', function () {
    $this->testServiceWithQuota('FraudMLService', 'process', 1, 10, []);
});

test('FraudMLService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('FraudMLService'));
        $service->process([]);
    }, 10);
});

test('FraudMLService has proper caching', function () {
    $cacheKey = 'fraudml:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('FraudMLService'));

        return $service->getData(1);
    });
});

test('FraudMLService dispatches proper events', function () {
    $eventClass = "App\Domains\FraudML\Events\FraudMLProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('FraudMLService'));
        $service->process([]);
    });
});

test('FraudMLService dispatches proper jobs', function () {
    $jobClass = "App\Domains\FraudML\Jobs\ProcessFraudMLJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('FraudMLService'));
        $service->processAsync([]);
    });
});

test('FraudMLService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('FraudMLService'));
        $service->process([]);
    }, \Exception::class);
});

test('FraudMLService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('FraudMLService'));
        $service->process([]);
    }, 'FraudMLService processed');
});

test('FraudMLService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
