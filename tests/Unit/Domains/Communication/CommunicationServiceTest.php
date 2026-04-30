<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Communication;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Communication');
});

test('CommunicationService exists and is instantiable', function () {
    $this->assertServiceExists('CommunicationService');
});

test('CommunicationService follows clean architecture', function () {
    $this->assertCleanArchitecture('CommunicationService');
});

test('CommunicationService performs fraud check', function () {
    $this->testServiceWithFraudCheck('CommunicationService', 'process', []);
});

test('CommunicationService enforces quota limits', function () {
    $this->testServiceWithQuota('CommunicationService', 'process', 1, 10, []);
});

test('CommunicationService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('CommunicationService'));
        $service->process([]);
    }, 10);
});

test('CommunicationService has proper caching', function () {
    $cacheKey = 'communication:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('CommunicationService'));

        return $service->getData(1);
    });
});

test('CommunicationService dispatches proper events', function () {
    $eventClass = "App\Domains\Communication\Events\CommunicationProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('CommunicationService'));
        $service->process([]);
    });
});

test('CommunicationService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Communication\Jobs\ProcessCommunicationJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('CommunicationService'));
        $service->processAsync([]);
    });
});

test('CommunicationService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('CommunicationService'));
        $service->process([]);
    }, \Exception::class);
});

test('CommunicationService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('CommunicationService'));
        $service->process([]);
    }, 'CommunicationService processed');
});

test('CommunicationService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
