<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\CarRental;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('CarRental');
});

test('CarRentalService exists and is instantiable', function () {
    $this->assertServiceExists('CarRentalService');
});

test('CarRentalService follows clean architecture', function () {
    $this->assertCleanArchitecture('CarRentalService');
});

test('CarRentalService performs fraud check', function () {
    $this->testServiceWithFraudCheck('CarRentalService', 'process', []);
});

test('CarRentalService enforces quota limits', function () {
    $this->testServiceWithQuota('CarRentalService', 'process', 1, 10, []);
});

test('CarRentalService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('CarRentalService'));
        $service->process([]);
    }, 10);
});

test('CarRentalService has proper caching', function () {
    $cacheKey = 'carrental:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('CarRentalService'));

        return $service->getData(1);
    });
});

test('CarRentalService dispatches proper events', function () {
    $eventClass = "App\Domains\CarRental\Events\CarRentalProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('CarRentalService'));
        $service->process([]);
    });
});

test('CarRentalService dispatches proper jobs', function () {
    $jobClass = "App\Domains\CarRental\Jobs\ProcessCarRentalJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('CarRentalService'));
        $service->processAsync([]);
    });
});

test('CarRentalService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('CarRentalService'));
        $service->process([]);
    }, \Exception::class);
});

test('CarRentalService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('CarRentalService'));
        $service->process([]);
    }, 'CarRentalService processed');
});

test('CarRentalService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
