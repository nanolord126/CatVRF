<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Domain\Services;

use Modules\FraudDetection\Application\DTOs\FraudCheckData;
use Modules\FraudDetection\Domain\ValueObjects\FraudScore;

/**
 * Интерфейс для сервиса ML-скоринга.
 * Реализация может быть подменена на PyTorch, TensorFlow, etc.
 */
interface FraudScoringServiceInterface
{
    public function getScore(FraudCheckData $data): FraudScore;
}
