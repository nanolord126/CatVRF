<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\PersonalDevelopment;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('PersonalDevelopment');
});

test('PersonalDevelopmentService exists and is instantiable', function () {
    $this->assertServiceExists('PersonalDevelopmentService');
});

test('PersonalDevelopmentService follows clean architecture', function () {
    $this->assertCleanArchitecture('PersonalDevelopmentService');
});

test('PersonalDevelopmentService performs fraud check', function () {
    $this->testServiceWithFraudCheck('PersonalDevelopmentService', 'process', []);
});

test('PersonalDevelopmentService enforces quota limits', function () {
    $this->testServiceWithQuota('PersonalDevelopmentService', 'process', 1, 10, []);
});

test('PersonalDevelopmentService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('PersonalDevelopmentService'));
        $service->process([]);
    }, 10);
});

test('PersonalDevelopmentService has proper caching', function () {
    $cacheKey = 'personaldevelopment:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('PersonalDevelopmentService'));

        return $service->getData(1);
    });
});

test('PersonalDevelopmentService dispatches proper events', function () {
    $eventClass = "App\Domains\PersonalDevelopment\Events\PersonalDevelopmentProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('PersonalDevelopmentService'));
        $service->process([]);
    });
});

test('PersonalDevelopmentService dispatches proper jobs', function () {
    $jobClass = "App\Domains\PersonalDevelopment\Jobs\ProcessPersonalDevelopmentJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('PersonalDevelopmentService'));
        $service->processAsync([]);
    });
});

test('PersonalDevelopmentService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('PersonalDevelopmentService'));
        $service->process([]);
    }, \Exception::class);
});

test('PersonalDevelopmentService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('PersonalDevelopmentService'));
        $service->process([]);
    }, 'PersonalDevelopmentService processed');
});

test('PersonalDevelopmentService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
