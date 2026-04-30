<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Tests\TestCase;
use Modules\FraudDetection\Application\Services\FraudControlService;
use Modules\FraudDetection\Application\Services\FraudMLService;
use Modules\FraudDetection\Domain\Entities\FraudAlert;
use Modules\FraudDetection\Domain\Enums\FraudSeverity;
use Modules\FraudDetection\Domain\Enums\FraudType;
use Modules\FraudDetection\Domain\Repositories\FraudAlertRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

abstract class BaseFraudTest extends TestCase
{
    use RefreshDatabase;

    protected FraudControlService $fraudControl;
    protected FraudMLService $fraudML;
    protected FraudAlertRepositoryInterface $fraudAlertRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraudControl = app(FraudControlService::class);
        $this->fraudML = app(FraudMLService::class);
        $this->fraudAlertRepo = app(FraudAlertRepositoryInterface::class);
    }

    protected function assertFraudAlertCreated(
        string $entityType,
        int $entityId,
        FraudType $fraudType,
        FraudSeverity $severity = FraudSeverity::High
    ): void {
        $alert = $this->fraudAlertRepo->findByEntity($entityType, $entityId);

        $this->assertNotNull($alert, 'Fraud alert should be created');
        $this->assertEquals($fraudType, $alert->fraudType);
        $this->assertEquals($severity, $alert->severity);
        $this->assertFalse($alert->isResolved());
    }

    protected function assertNoFraudAlert(string $entityType, int $entityId): void
    {
        $alert = $this->fraudAlertRepo->findByEntity($entityType, $entityId);
        $this->assertNull($alert, 'No fraud alert should exist');
    }

    protected function simulateSuspiciousActivity(array $params): array
    {
        return [
            'ip_address' => $params['ip_address'] ?? '192.168.1.1',
            'user_agent' => $params['user_agent'] ?? 'Mozilla/5.0',
            'device_fingerprint' => $params['device_fingerprint'] ?? 'fp_12345',
            'session_id' => $params['session_id'] ?? 'sess_12345',
            'request_count' => $params['request_count'] ?? 1,
            'time_window_seconds' => $params['time_window_seconds'] ?? 60,
        ];
    }

    protected function createTestTransaction(array $overrides = []): array
    {
        return array_merge([
            'amount' => 10000,
            'currency' => 'RUB',
            'user_id' => 1,
            'tenant_id' => 1,
            'payment_method' => 'card',
            'ip_address' => '192.168.1.1',
            'device_fingerprint' => 'fp_12345',
        ], $overrides);
    }
}
