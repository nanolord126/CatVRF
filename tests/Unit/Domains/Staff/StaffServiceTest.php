<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Staff;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Staff');
});

test('StaffService exists and is instantiable', function () {
    $this->assertServiceExists('StaffService');
});

test('StaffService follows clean architecture', function () {
    $this->assertCleanArchitecture('StaffService');
});

test('StaffService performs fraud check', function () {
    $this->testServiceWithFraudCheck('StaffService', 'process', []);
});

test('StaffService enforces quota limits', function () {
    $this->testServiceWithQuota('StaffService', 'process', 1, 10, []);
});

test('StaffService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('StaffService'));
        $service->process([]);
    }, 10);
});

test('StaffService has proper caching', function () {
    $cacheKey = 'staff:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('StaffService'));

        return $service->getData(1);
    });
});

test('StaffService dispatches proper events', function () {
    $eventClass = "App\Domains\Staff\Events\StaffProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('StaffService'));
        $service->process([]);
    });
});

test('StaffService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Staff\Jobs\ProcessStaffJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('StaffService'));
        $service->processAsync([]);
    });
});

test('StaffService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('StaffService'));
        $service->process([]);
    }, \Exception::class);
});

test('StaffService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('StaffService'));
        $service->process([]);
    }, 'StaffService processed');
});

test('StaffService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
