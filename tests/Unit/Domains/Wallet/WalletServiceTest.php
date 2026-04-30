<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet;

use Tests\BaseVerticalTestCase;

// Pest test using modern declarative syntax
uses(BaseVerticalTestCase::class);

beforeEach(function () {
    $this->setVerticalContext('Wallet');
});

test('WalletService exists and is instantiable', function () {
    $this->assertServiceExists('WalletService');
});

test('WalletService follows clean architecture', function () {
    $this->assertCleanArchitecture('WalletService');
});

test('WalletService performs fraud check', function () {
    $this->testServiceWithFraudCheck('WalletService', 'process', []);
});

test('WalletService enforces quota limits', function () {
    $this->testServiceWithQuota('WalletService', 'process', 1, 10, []);
});

test('WalletService handles concurrent operations', function () {
    $this->assertNoRaceCondition(function () {
        // Simulate concurrent operation
        $service = app($this->getServiceClass('WalletService'));
        $service->process([]);
    }, 10);
});

test('WalletService has proper caching', function () {
    $cacheKey = 'wallet:data:1';

    $this->assertServiceCaching($cacheKey, function () {
        $service = app($this->getServiceClass('WalletService'));

        return $service->getData(1);
    });
});

test('WalletService dispatches proper events', function () {
    $eventClass = "App\Domains\Wallet\Events\WalletProcessed";

    $this->assertEventDispatched($eventClass, function () {
        $service = app($this->getServiceClass('WalletService'));
        $service->process([]);
    });
});

test('WalletService dispatches proper jobs', function () {
    $jobClass = "App\Domains\Wallet\Jobs\ProcessWalletJob";

    $this->assertJobDispatched($jobClass, function () {
        $service = app($this->getServiceClass('WalletService'));
        $service->processAsync([]);
    });
});

test('WalletService handles errors gracefully', function () {
    $this->assertErrorHandling(function () {
        $service = app($this->getServiceClass('WalletService'));
        $service->process([]);
    }, \Exception::class);
});

test('WalletService logs operations', function () {
    $this->assertServiceLogging(function () {
        $service = app($this->getServiceClass('WalletService'));
        $service->process([]);
    }, 'WalletService processed');
});

test('WalletService data is PII compliant', function () {
    $data = [
        'user_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    $this->assertVerticalDataPiiCompliant($data);
});
