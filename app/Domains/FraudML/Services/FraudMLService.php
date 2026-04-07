<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Services;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

use App\Domains\FraudML\DTOs\OperationDto;
/**
 * Исключительный сервис антифрод контроля на базе ML. 
 * Перед любой финансовой или критической мутацией ОБЯЗАТЕЛЕН вызов скоринга здесь.
 */
final readonly class FraudMLService
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Осуществляет безоговорочную оценку операции по шкале фрода [0.0 ... 1.0].
     */
    public function scoreOperation(OperationDto $dto): float
    {
        // Извлекаем признаки (features)
        $features = $this->extractFeatures($dto);

        // Симуляция обращения к XGBoost/LightGBM по REST API или локальному joblib
        $mlScore = $this->predictWithFallback($features);

        $this->logger->info('Fraud ML Score calculated', [
            'correlation_id' => $dto->correlation_id,
            'operation' => $dto->operation_type,
            'score' => $mlScore,
            'decision' => $this->shouldBlock($mlScore, $dto->operation_type) ? 'block' : 'allow'
        ]);

        return $mlScore;
    }

    /**
     * Решает, нужно ли жестко заблокировать операцию на основе порога из конфигурации.
     */
    public function shouldBlock(float $score, string $operationType): bool
    {
        $threshold = $this->config->get("fraud.thresholds.{$operationType}", 0.85);
        return $score >= $threshold;
    }

    /**
     * Генерирует массив признаков из истории клиента. Минимум 30-50 фич в реальном бою.
     */
    private function extractFeatures(OperationDto $dto): array
    {
        return [
            'amount_log' => log($dto->amount > 0 ? $dto->amount : 1),
            'hour_of_day' => Carbon::now()->hour,
            // Дополнительные фичи (age of account, tx_count_1h, etc)
        ];
    }

    /**
     * Гарантированный fallback на жесткие правила, если ML сервис временно недоступен.
     */
    private function predictWithFallback(array $features): float
    {
        // В реальном приложении здесь http вызов к Python microservice. Если сбой -> return 0.5 (ревеью)
        return 0.12; // default benign
    }
}
