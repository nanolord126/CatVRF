<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\OfficeCatering;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('OfficeCatering');
});

test('OfficeCateringService exists and is instantiable', function () {
    $this->assertServiceExists('OfficeCateringService');
});

test('OfficeCateringService follows clean architecture', function () {
    $this->assertCleanArchitecture('OfficeCateringService');
});

test('OfficeCateringService performs fraud check', function () {
    $this->testServiceWithFraudCheck('OfficeCateringService', 'process', []);
});

test('OfficeCateringService enforces quota limits', function () {
    $this->testServiceWithQuota('OfficeCateringService', 'process', 1, 10, []);
});

test('OfficeCateringService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('OfficeCateringService'));
        $service->process([]);
    }, 10);
});

test('OfficeCateringService has proper caching', function () {
    $cacheKey = 'officecatering:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('OfficeCateringService'));

        return $service->getData(1);
    });
});

test('OfficeCateringService dispatches proper events', function () {
    $eventClass = "App\Domains\OfficeCatering\Events\OfficeCateringProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('OfficeCateringService'));
        $service->process([]);
    });
});

test('OfficeCateringService dispatches proper jobs', function () {
    $jobClass = "App\Domains\OfficeCatering\Jobs\ProcessOfficeCateringJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('OfficeCateringService'));
        $service->processAsync([]);
    });
});

test('OfficeCateringService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('OfficeCateringService'));
        $service->process([]);
    }, \Exception::class);
});

test('OfficeCateringService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('OfficeCateringService'));
        $service->process([]);
    }, 'OfficeCateringService processed');
});

test('OfficeCateringService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
