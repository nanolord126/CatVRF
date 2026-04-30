<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Content;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Content');
});

test('ContentService exists and is instantiable', function () {
    $this->assertServiceExists('ContentService');
});

test('ContentService follows clean architecture', function () {
    $this->assertCleanArchitecture('ContentService');
});

test('ContentService performs fraud check', function () {
    $this->testServiceWithFraudCheck('ContentService', 'process', []);
});

test('ContentService enforces quota limits', function () {
    $this->testServiceWithQuota('ContentService', 'process', 1, 10, []);
});

test('ContentService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('ContentService'));
        $service->process([]);
    }, 10);
});

test('ContentService has proper caching', function () {
    $cacheKey = 'content:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('ContentService'));

        return $service->getData(1);
    });
});

test('ContentService dispatches proper events', function () {
    $eventClass = "App\Domains\Content\Events\ContentProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('ContentService'));
        $service->process([]);
    });
});

test('ContentService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Content\Jobs\ProcessContentJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('ContentService'));
        $service->processAsync([]);
    });
});

test('ContentService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('ContentService'));
        $service->process([]);
    }, \Exception::class);
});

test('ContentService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('ContentService'));
        $service->process([]);
    }, 'ContentService processed');
});

test('ContentService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
