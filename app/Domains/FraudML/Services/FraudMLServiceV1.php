<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Services;

use App\DTOs\Fraud\FraudCheckDTO;
use App\DTOs\Fraud\FraudResultDTO;

/**
 * FraudML Service V1 (Legacy Model)
 *
 * Original fraud detection model using rule-based and simple ML.
 */
final class FraudMLServiceV1
{
    public function check(FraudCheckDTO $dto): FraudResultDTO
    {
        // Simplified V1 implementation
        $fraudScore = $this->calculateFraudScore($dto);
        $isFraud = $fraudScore > 0.7;

        return new FraudResultDTO(
            fraudScore: $fraudScore,
            isFraud: $isFraud,
            modelVersion: 'v1',
            reasons: $this->getFraudReasons($dto, $fraudScore),
            requiresManualReview: $fraudScore > 0.5 && $fraudScore <= 0.7,
        );
    }

    private function calculateFraudScore(FraudCheckDTO $dto): float
    {
        $score = 0.0;

        // Rule-based scoring
        if ($dto->amount > 100000) {
            $score += 0.3;
        }

        if ($dto->isInternational) {
            $score += 0.2;
        }

        if ($dto->deviceIdChanged) {
            $score += 0.25;
        }

        if ($dto->ipAddressChanged) {
            $score += 0.25;
        }

        return min($score, 1.0);
    }

    private function getFraudReasons(FraudCheckDTO $dto, float $score): array
    {
        $reasons = [];

        if ($dto->amount > 100000) {
            $reasons[] = 'high_amount';
        }

        if ($dto->isInternational) {
            $reasons[] = 'international_transaction';
        }

        if ($dto->deviceIdChanged) {
            $reasons[] = 'device_change';
        }

        if ($dto->ipAddressChanged) {
            $reasons[] = 'ip_change';
        }

        return $reasons;
    }
}
