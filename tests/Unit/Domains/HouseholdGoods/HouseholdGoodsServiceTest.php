<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\HouseholdGoods;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('HouseholdGoods');
});

test('HouseholdGoodsService exists and is instantiable', function () {
    $this->assertServiceExists('HouseholdGoodsService');
});

test('HouseholdGoodsService follows clean architecture', function () {
    $this->assertCleanArchitecture('HouseholdGoodsService');
});

test('HouseholdGoodsService performs fraud check', function () {
    $this->testServiceWithFraudCheck('HouseholdGoodsService', 'process', []);
});

test('HouseholdGoodsService enforces quota limits', function () {
    $this->testServiceWithQuota('HouseholdGoodsService', 'process', 1, 10, []);
});

test('HouseholdGoodsService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('HouseholdGoodsService'));
        $service->process([]);
    }, 10);
});

test('HouseholdGoodsService has proper caching', function () {
    $cacheKey = 'householdgoods:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('HouseholdGoodsService'));

        return $service->getData(1);
    });
});

test('HouseholdGoodsService dispatches proper events', function () {
    $eventClass = "App\Domains\HouseholdGoods\Events\HouseholdGoodsProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('HouseholdGoodsService'));
        $service->process([]);
    });
});

test('HouseholdGoodsService dispatches proper jobs', function () {
    $jobClass = "App\Domains\HouseholdGoods\Jobs\ProcessHouseholdGoodsJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('HouseholdGoodsService'));
        $service->processAsync([]);
    });
});

test('HouseholdGoodsService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('HouseholdGoodsService'));
        $service->process([]);
    }, \Exception::class);
});

test('HouseholdGoodsService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('HouseholdGoodsService'));
        $service->process([]);
    }, 'HouseholdGoodsService processed');
});

test('HouseholdGoodsService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
