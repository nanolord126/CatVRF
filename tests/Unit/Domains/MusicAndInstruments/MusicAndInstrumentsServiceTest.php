<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\MusicAndInstruments;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('MusicAndInstruments');
});

test('MusicAndInstrumentsService exists and is instantiable', function () {
    $this->assertServiceExists('MusicAndInstrumentsService');
});

test('MusicAndInstrumentsService follows clean architecture', function () {
    $this->assertCleanArchitecture('MusicAndInstrumentsService');
});

test('MusicAndInstrumentsService performs fraud check', function () {
    $this->testServiceWithFraudCheck('MusicAndInstrumentsService', 'process', []);
});

test('MusicAndInstrumentsService enforces quota limits', function () {
    $this->testServiceWithQuota('MusicAndInstrumentsService', 'process', 1, 10, []);
});

test('MusicAndInstrumentsService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('MusicAndInstrumentsService'));
        $service->process([]);
    }, 10);
});

test('MusicAndInstrumentsService has proper caching', function () {
    $cacheKey = 'musicandinstruments:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('MusicAndInstrumentsService'));

        return $service->getData(1);
    });
});

test('MusicAndInstrumentsService dispatches proper events', function () {
    $eventClass = "App\Domains\MusicAndInstruments\Events\MusicAndInstrumentsProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('MusicAndInstrumentsService'));
        $service->process([]);
    });
});

test('MusicAndInstrumentsService dispatches proper jobs', function () {
    $jobClass = "App\Domains\MusicAndInstruments\Jobs\ProcessMusicAndInstrumentsJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('MusicAndInstrumentsService'));
        $service->processAsync([]);
    });
});

test('MusicAndInstrumentsService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('MusicAndInstrumentsService'));
        $service->process([]);
    }, \Exception::class);
});

test('MusicAndInstrumentsService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('MusicAndInstrumentsService'));
        $service->process([]);
    }, 'MusicAndInstrumentsService processed');
});

test('MusicAndInstrumentsService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
