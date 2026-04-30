<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Food;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Food');
});

test('FoodService exists and is instantiable', function () {
    $this->assertServiceExists('FoodService');
});

test('FoodService follows clean architecture', function () {
    $this->assertCleanArchitecture('FoodService');
});

test('FoodService performs fraud check', function () {
    $this->testServiceWithFraudCheck('FoodService', 'process', []);
});

test('FoodService enforces quota limits', function () {
    $this->testServiceWithQuota('FoodService', 'process', 1, 10, []);
});

test('FoodService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('FoodService'));
        $service->process([]);
    }, 10);
});

test('FoodService has proper caching', function () {
    $cacheKey = 'food:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('FoodService'));

        return $service->getData(1);
    });
});

test('FoodService dispatches proper events', function () {
    $eventClass = "App\Domains\Food\Events\FoodProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('FoodService'));
        $service->process([]);
    });
});

test('FoodService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Food\Jobs\ProcessFoodJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('FoodService'));
        $service->processAsync([]);
    });
});

test('FoodService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('FoodService'));
        $service->process([]);
    }, \Exception::class);
});

test('FoodService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('FoodService'));
        $service->process([]);
    }, 'FoodService processed');
});

test('FoodService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
