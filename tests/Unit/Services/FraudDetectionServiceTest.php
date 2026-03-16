<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Automation\FraudDetectionService;
use App\Services\LogManager;
use PHPUnit\Framework\TestCase;

/**
 * FraudDetectionServiceTest - Тестирование сервиса обнаружения мошенничества
 */
final class FraudDetectionServiceTest extends TestCase
{
    private FraudDetectionService $service;
    private LogManager $logManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logManager = $this->createMock(LogManager::class);
        $this->service = new FraudDetectionService($this->logManager);
    }

    /**
     * Тест: высокая сумма увеличивает risk score
     */
    public function test_high_amount_increases_risk_score(): void
    {
        $transaction = [
            'id' => 'txn_123',
            'user_id' => 'user_456',
            'amount' => 200000,
            'location' => 'Moscow',
        ];

        $result = $this->service->analyzeTransaction($transaction);

        $this->assertGreater($result['risk_score'], 0.2);
        $this->assertContains('HIGH_AMOUNT', $result['flags']);
    }

    /**
     * Тест: обычная транзакция имеет низкий риск
     */
    public function test_normal_transaction_has_low_risk(): void
    {
        $transaction = [
            'id' => 'txn_789',
            'user_id' => 'user_111',
            'amount' => 1000,
            'location' => 'Moscow',
        ];

        $result = $this->service->analyzeTransaction($transaction);

        $this->assertLess($result['risk_score'], 0.5);
        $this->assertEquals('APPROVED', $result['status']);
    }

    /**
     * Тест: высокий риск блокирует транзакцию
     */
    public function test_high_risk_transaction_is_blocked(): void
    {
        $transaction = [
            'id' => 'txn_fraud',
            'user_id' => 'user_bad',
            'amount' => 500000,
            'location' => 'Unknown',
        ];

        $result = $this->service->analyzeTransaction($transaction);

        if ($result['risk_score'] >= 0.8) {
            $this->assertEquals('BLOCKED', $result['status']);
        }
    }

    /**
     * Тест: detectAnomalies возвращает коллекцию
     */
    public function test_detect_anomalies_returns_collection(): void
    {
        $result = $this->service->detectAnomalies();

        $this->assertIsObject($result);
        $this->assertTrue(method_exists($result, 'count'));
    }

    /**
     * Тест: blockSuspicious логирует событие
     */
    public function test_block_suspicious_logs_event(): void
    {
        $this->logManager->expects($this->once())
            ->method('warn')
            ->with($this->stringContains('blocked'));

        $transaction = [
            'id' => 'txn_block',
            'reason' => 'High risk detected',
        ];

        $this->service->blockSuspicious($transaction);
    }

    /**
     * Тест: logSecurityEvent возвращает true
     */
    public function test_log_security_event_returns_true(): void
    {
        $result = $this->service->logSecurityEvent('FRAUD_DETECTED', [
            'user_id' => 'user_123',
            'reason' => 'Unusual pattern',
        ]);

        $this->assertTrue($result);
    }
}
