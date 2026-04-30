<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Insurance;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Insurance');
});

test('InsuranceService exists and is instantiable', function () {
    $this->assertServiceExists('InsuranceService');
});

test('InsuranceService follows clean architecture', function () {
    $this->assertCleanArchitecture('InsuranceService');
});

test('InsuranceService performs fraud check', function () {
    $this->testServiceWithFraudCheck('InsuranceService', 'process', []);
});

test('InsuranceService enforces quota limits', function () {
    $this->testServiceWithQuota('InsuranceService', 'process', 1, 10, []);
});

test('InsuranceService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('InsuranceService'));
        $service->process([]);
    }, 10);
});

test('InsuranceService has proper caching', function () {
    $cacheKey = 'insurance:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('InsuranceService'));

        return $service->getData(1);
    });
});

test('InsuranceService dispatches proper events', function () {
    $eventClass = "App\Domains\Insurance\Events\InsuranceProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('InsuranceService'));
        $service->process([]);
    });
});

test('InsuranceService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Insurance\Jobs\ProcessInsuranceJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('InsuranceService'));
        $service->processAsync([]);
    });
});

test('InsuranceService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('InsuranceService'));
        $service->process([]);
    }, \Exception::class);
});

test('InsuranceService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('InsuranceService'));
        $service->process([]);
    }, 'InsuranceService processed');
});

test('InsuranceService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
