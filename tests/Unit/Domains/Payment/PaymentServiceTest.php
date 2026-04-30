<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Payment');
});

test('PaymentService exists and is instantiable', function () {
    $this->assertServiceExists('PaymentService');
});

test('PaymentService follows clean architecture', function () {
    $this->assertCleanArchitecture('PaymentService');
});

test('PaymentService performs fraud check', function () {
    $this->testServiceWithFraudCheck('PaymentService', 'process', []);
});

test('PaymentService enforces quota limits', function () {
    $this->testServiceWithQuota('PaymentService', 'process', 1, 10, []);
});

test('PaymentService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('PaymentService'));
        $service->process([]);
    }, 10);
});

test('PaymentService has proper caching', function () {
    $cacheKey = 'payment:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('PaymentService'));

        return $service->getData(1);
    });
});

test('PaymentService dispatches proper events', function () {
    $eventClass = "App\Domains\Payment\Events\PaymentProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('PaymentService'));
        $service->process([]);
    });
});

test('PaymentService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Payment\Jobs\ProcessPaymentJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('PaymentService'));
        $service->processAsync([]);
    });
});

test('PaymentService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('PaymentService'));
        $service->process([]);
    }, \Exception::class);
});

test('PaymentService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('PaymentService'));
        $service->process([]);
    }, 'PaymentService processed');
});

test('PaymentService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
