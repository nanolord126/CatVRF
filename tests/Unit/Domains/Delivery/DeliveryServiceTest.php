<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Delivery;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Delivery');
});

test('DeliveryService exists and is instantiable', function () {
    $this->assertServiceExists('DeliveryService');
});

test('DeliveryService follows clean architecture', function () {
    $this->assertCleanArchitecture('DeliveryService');
});

test('DeliveryService performs fraud check', function () {
    $this->testServiceWithFraudCheck('DeliveryService', 'process', []);
});

test('DeliveryService enforces quota limits', function () {
    $this->testServiceWithQuota('DeliveryService', 'process', 1, 10, []);
});

test('DeliveryService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('DeliveryService'));
        $service->process([]);
    }, 10);
});

test('DeliveryService has proper caching', function () {
    $cacheKey = 'delivery:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('DeliveryService'));

        return $service->getData(1);
    });
});

test('DeliveryService dispatches proper events', function () {
    $eventClass = "App\Domains\Delivery\Events\DeliveryProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('DeliveryService'));
        $service->process([]);
    });
});

test('DeliveryService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Delivery\Jobs\ProcessDeliveryJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('DeliveryService'));
        $service->processAsync([]);
    });
});

test('DeliveryService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('DeliveryService'));
        $service->process([]);
    }, \Exception::class);
});

test('DeliveryService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('DeliveryService'));
        $service->process([]);
    }, 'DeliveryService processed');
});

test('DeliveryService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
