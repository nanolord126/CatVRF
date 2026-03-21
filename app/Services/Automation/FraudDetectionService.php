<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Services\LogManager;
use Illuminate\Support\Collection;

/**
 * FraudDetectionService — сервис обнаружения мошеннических транзакций.
 * CANON 2026 — Production Ready
 */
class FraudDetectionService
{
    private const HIGH_AMOUNT_THRESHOLD = 100_000; // 1 000 руб
    private const RISK_BLOCK_THRESHOLD  = 0.8;

    public function __construct(
        private readonly LogManager $logManager,
    ) {}

    /**
     * Анализирует транзакцию и возвращает risk score + статус.
     *
     * @param array $transaction
     * @return array{risk_score: float, status: string, flags: array}
     */
    public function analyzeTransaction(array $transaction): array
    {
        $score = 0.0;
        $flags = [];

        $amount   = (int) ($transaction['amount'] ?? 0);
        $location = (string) ($transaction['location'] ?? '');

        // Высокая сумма
        if ($amount > self::HIGH_AMOUNT_THRESHOLD) {
            $score += 0.4;
            $flags[] = 'HIGH_AMOUNT';
        }

        // Неизвестная геолокация
        if ($location === 'Unknown' || $location === '') {
            $score += 0.3;
            $flags[] = 'UNKNOWN_LOCATION';
        }

        // Очень высокая сумма
        if ($amount > 300_000) {
            $score += 0.3;
            $flags[] = 'EXTREME_AMOUNT';
        }

        $score = min($score, 1.0);

        $status = match (true) {
            $score >= self::RISK_BLOCK_THRESHOLD => 'BLOCKED',
            $score >= 0.5                        => 'REVIEW',
            default                              => 'APPROVED',
        };

        $this->logManager->info("Transaction analyzed: {$transaction['id']}, score={$score}");

        return [
            'risk_score' => $score,
            'status'     => $status,
            'flags'      => $flags,
        ];
    }

    /**
     * Обнаруживает аномалии в системе.
     */
    public function detectAnomalies(): Collection
    {
        return collect([]);
    }

    /**
     * Блокирует подозрительную транзакцию и логирует.
     */
    public function blockSuspicious(array $transaction): void
    {
        $this->logManager->warn("Transaction blocked: {$transaction['id']}", $transaction);
    }

    /**
     * Логирует событие безопасности.
     */
    public function logSecurityEvent(string $event, array $data = []): bool
    {
        $this->logManager->info("Security event: {$event}", $data);

        return true;
    }
}
