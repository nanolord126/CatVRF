<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\HomeServices;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('HomeServices');
});

test('HomeServicesService exists and is instantiable', function () {
    $this->assertServiceExists('HomeServicesService');
});

test('HomeServicesService follows clean architecture', function () {
    $this->assertCleanArchitecture('HomeServicesService');
});

test('HomeServicesService performs fraud check', function () {
    $this->testServiceWithFraudCheck('HomeServicesService', 'process', []);
});

test('HomeServicesService enforces quota limits', function () {
    $this->testServiceWithQuota('HomeServicesService', 'process', 1, 10, []);
});

test('HomeServicesService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('HomeServicesService'));
        $service->process([]);
    }, 10);
});

test('HomeServicesService has proper caching', function () {
    $cacheKey = 'homeservices:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('HomeServicesService'));

        return $service->getData(1);
    });
});

test('HomeServicesService dispatches proper events', function () {
    $eventClass = "App\Domains\HomeServices\Events\HomeServicesProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('HomeServicesService'));
        $service->process([]);
    });
});

test('HomeServicesService dispatches proper jobs', function () {
    $jobClass = "App\Domains\HomeServices\Jobs\ProcessHomeServicesJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('HomeServicesService'));
        $service->processAsync([]);
    });
});

test('HomeServicesService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('HomeServicesService'));
        $service->process([]);
    }, \Exception::class);
});

test('HomeServicesService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('HomeServicesService'));
        $service->process([]);
    }, 'HomeServicesService processed');
});

test('HomeServicesService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
