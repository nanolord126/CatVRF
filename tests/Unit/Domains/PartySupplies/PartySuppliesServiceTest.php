<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\PartySupplies;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('PartySupplies');
});

test('PartySuppliesService exists and is instantiable', function () {
    $this->assertServiceExists('PartySuppliesService');
});

test('PartySuppliesService follows clean architecture', function () {
    $this->assertCleanArchitecture('PartySuppliesService');
});

test('PartySuppliesService performs fraud check', function () {
    $this->testServiceWithFraudCheck('PartySuppliesService', 'process', []);
});

test('PartySuppliesService enforces quota limits', function () {
    $this->testServiceWithQuota('PartySuppliesService', 'process', 1, 10, []);
});

test('PartySuppliesService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('PartySuppliesService'));
        $service->process([]);
    }, 10);
});

test('PartySuppliesService has proper caching', function () {
    $cacheKey = 'partysupplies:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('PartySuppliesService'));

        return $service->getData(1);
    });
});

test('PartySuppliesService dispatches proper events', function () {
    $eventClass = "App\Domains\PartySupplies\Events\PartySuppliesProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('PartySuppliesService'));
        $service->process([]);
    });
});

test('PartySuppliesService dispatches proper jobs', function () {
    $jobClass = "App\Domains\PartySupplies\Jobs\ProcessPartySuppliesJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('PartySuppliesService'));
        $service->processAsync([]);
    });
});

test('PartySuppliesService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('PartySuppliesService'));
        $service->process([]);
    }, \Exception::class);
});

test('PartySuppliesService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('PartySuppliesService'));
        $service->process([]);
    }, 'PartySuppliesService processed');
});

test('PartySuppliesService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
