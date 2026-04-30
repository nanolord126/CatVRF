<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\CleaningServices;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('CleaningServices');
});

test('CleaningServicesService exists and is instantiable', function () {
    $this->assertServiceExists('CleaningServicesService');
});

test('CleaningServicesService follows clean architecture', function () {
    $this->assertCleanArchitecture('CleaningServicesService');
});

test('CleaningServicesService performs fraud check', function () {
    $this->testServiceWithFraudCheck('CleaningServicesService', 'process', []);
});

test('CleaningServicesService enforces quota limits', function () {
    $this->testServiceWithQuota('CleaningServicesService', 'process', 1, 10, []);
});

test('CleaningServicesService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('CleaningServicesService'));
        $service->process([]);
    }, 10);
});

test('CleaningServicesService has proper caching', function () {
    $cacheKey = 'cleaningservices:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('CleaningServicesService'));

        return $service->getData(1);
    });
});

test('CleaningServicesService dispatches proper events', function () {
    $eventClass = "App\Domains\CleaningServices\Events\CleaningServicesProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('CleaningServicesService'));
        $service->process([]);
    });
});

test('CleaningServicesService dispatches proper jobs', function () {
    $jobClass = "App\Domains\CleaningServices\Jobs\ProcessCleaningServicesJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('CleaningServicesService'));
        $service->processAsync([]);
    });
});

test('CleaningServicesService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('CleaningServicesService'));
        $service->process([]);
    }, \Exception::class);
});

test('CleaningServicesService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('CleaningServicesService'));
        $service->process([]);
    }, 'CleaningServicesService processed');
});

test('CleaningServicesService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
