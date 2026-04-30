<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Freelance;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Freelance');
});

test('FreelanceService exists and is instantiable', function () {
    $this->assertServiceExists('FreelanceService');
});

test('FreelanceService follows clean architecture', function () {
    $this->assertCleanArchitecture('FreelanceService');
});

test('FreelanceService performs fraud check', function () {
    $this->testServiceWithFraudCheck('FreelanceService', 'process', []);
});

test('FreelanceService enforces quota limits', function () {
    $this->testServiceWithQuota('FreelanceService', 'process', 1, 10, []);
});

test('FreelanceService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('FreelanceService'));
        $service->process([]);
    }, 10);
});

test('FreelanceService has proper caching', function () {
    $cacheKey = 'freelance:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('FreelanceService'));

        return $service->getData(1);
    });
});

test('FreelanceService dispatches proper events', function () {
    $eventClass = "App\Domains\Freelance\Events\FreelanceProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('FreelanceService'));
        $service->process([]);
    });
});

test('FreelanceService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Freelance\Jobs\ProcessFreelanceJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('FreelanceService'));
        $service->processAsync([]);
    });
});

test('FreelanceService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('FreelanceService'));
        $service->process([]);
    }, \Exception::class);
});

test('FreelanceService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('FreelanceService'));
        $service->process([]);
    }, 'FreelanceService processed');
});

test('FreelanceService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
