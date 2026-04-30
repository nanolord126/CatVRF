<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Fitness;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Fitness');
});

test('FitnessService exists and is instantiable', function () {
    $this->assertServiceExists('FitnessService');
});

test('FitnessService follows clean architecture', function () {
    $this->assertCleanArchitecture('FitnessService');
});

test('FitnessService performs fraud check', function () {
    $this->testServiceWithFraudCheck('FitnessService', 'process', []);
});

test('FitnessService enforces quota limits', function () {
    $this->testServiceWithQuota('FitnessService', 'process', 1, 10, []);
});

test('FitnessService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('FitnessService'));
        $service->process([]);
    }, 10);
});

test('FitnessService has proper caching', function () {
    $cacheKey = 'fitness:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('FitnessService'));

        return $service->getData(1);
    });
});

test('FitnessService dispatches proper events', function () {
    $eventClass = "App\Domains\Fitness\Events\FitnessProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('FitnessService'));
        $service->process([]);
    });
});

test('FitnessService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Fitness\Jobs\ProcessFitnessJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('FitnessService'));
        $service->processAsync([]);
    });
});

test('FitnessService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('FitnessService'));
        $service->process([]);
    }, \Exception::class);
});

test('FitnessService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('FitnessService'));
        $service->process([]);
    }, 'FitnessService processed');
});

test('FitnessService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
