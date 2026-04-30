<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Confectionery;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Confectionery');
});

test('ConfectioneryService exists and is instantiable', function () {
    $this->assertServiceExists('ConfectioneryService');
});

test('ConfectioneryService follows clean architecture', function () {
    $this->assertCleanArchitecture('ConfectioneryService');
});

test('ConfectioneryService performs fraud check', function () {
    $this->testServiceWithFraudCheck('ConfectioneryService', 'process', []);
});

test('ConfectioneryService enforces quota limits', function () {
    $this->testServiceWithQuota('ConfectioneryService', 'process', 1, 10, []);
});

test('ConfectioneryService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('ConfectioneryService'));
        $service->process([]);
    }, 10);
});

test('ConfectioneryService has proper caching', function () {
    $cacheKey = 'confectionery:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('ConfectioneryService'));

        return $service->getData(1);
    });
});

test('ConfectioneryService dispatches proper events', function () {
    $eventClass = "App\Domains\Confectionery\Events\ConfectioneryProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('ConfectioneryService'));
        $service->process([]);
    });
});

test('ConfectioneryService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Confectionery\Jobs\ProcessConfectioneryJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('ConfectioneryService'));
        $service->processAsync([]);
    });
});

test('ConfectioneryService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('ConfectioneryService'));
        $service->process([]);
    }, \Exception::class);
});

test('ConfectioneryService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('ConfectioneryService'));
        $service->process([]);
    }, 'ConfectioneryService processed');
});

test('ConfectioneryService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
