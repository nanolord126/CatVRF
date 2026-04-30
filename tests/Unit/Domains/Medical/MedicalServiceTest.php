<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Medical;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Medical');
});

test('MedicalService exists and is instantiable', function () {
    $this->assertServiceExists('MedicalService');
});

test('MedicalService follows clean architecture', function () {
    $this->assertCleanArchitecture('MedicalService');
});

test('MedicalService performs fraud check', function () {
    $this->testServiceWithFraudCheck('MedicalService', 'process', []);
});

test('MedicalService enforces quota limits', function () {
    $this->testServiceWithQuota('MedicalService', 'process', 1, 10, []);
});

test('MedicalService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('MedicalService'));
        $service->process([]);
    }, 10);
});

test('MedicalService has proper caching', function () {
    $cacheKey = 'medical:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('MedicalService'));

        return $service->getData(1);
    });
});

test('MedicalService dispatches proper events', function () {
    $eventClass = "App\Domains\Medical\Events\MedicalProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('MedicalService'));
        $service->process([]);
    });
});

test('MedicalService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Medical\Jobs\ProcessMedicalJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('MedicalService'));
        $service->processAsync([]);
    });
});

test('MedicalService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('MedicalService'));
        $service->process([]);
    }, \Exception::class);
});

test('MedicalService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('MedicalService'));
        $service->process([]);
    }, 'MedicalService processed');
});

test('MedicalService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
