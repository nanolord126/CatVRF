<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Analytics;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Analytics');
});

test('AnalyticsService exists and is instantiable', function () {
    $this->assertServiceExists('AnalyticsService');
});

test('AnalyticsService follows clean architecture', function () {
    $this->assertCleanArchitecture('AnalyticsService');
});

test('AnalyticsService performs fraud check', function () {
    $this->testServiceWithFraudCheck('AnalyticsService', 'process', []);
});

test('AnalyticsService enforces quota limits', function () {
    $this->testServiceWithQuota('AnalyticsService', 'process', 1, 10, []);
});

test('AnalyticsService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('AnalyticsService'));
        $service->process([]);
    }, 10);
});

test('AnalyticsService has proper caching', function () {
    $cacheKey = 'analytics:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('AnalyticsService'));

        return $service->getData(1);
    });
});

test('AnalyticsService dispatches proper events', function () {
    $eventClass = "App\Domains\Analytics\Events\AnalyticsProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('AnalyticsService'));
        $service->process([]);
    });
});

test('AnalyticsService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Analytics\Jobs\ProcessAnalyticsJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('AnalyticsService'));
        $service->processAsync([]);
    });
});

test('AnalyticsService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('AnalyticsService'));
        $service->process([]);
    }, \Exception::class);
});

test('AnalyticsService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('AnalyticsService'));
        $service->process([]);
    }, 'AnalyticsService processed');
});

test('AnalyticsService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
