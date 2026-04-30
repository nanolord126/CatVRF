<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Services;

use App\DTOs\Fraud\FraudCheckDTO;
use App\DTOs\Fraud\FraudResultDTO;
use Illuminate\Filesystem\FilesystemManager;

/**
 * FraudML Service V2 (New Model)
 *
 * Advanced fraud detection using LightGBM with behavioral analysis.
 */
final class FraudMLServiceV2
{
    public function __construct(
        private readonly FilesystemManager $storage,
    ) {}
    public function check(FraudCheckDTO $dto): FraudResultDTO
    {
        // Load the trained model
        $model = $this->loadModel();

        // Prepare features
        $features = $this->prepareFeatures($dto);

        // Run prediction
        $fraudScore = $this->predict($model, $features);
        $isFraud = $fraudScore > 0.7;

        return new FraudResultDTO(
            fraudScore: $fraudScore,
            isFraud: $isFraud,
            modelVersion: 'v2',
            reasons: $this->getFraudReasons($dto, $fraudScore),
            requiresManualReview: $fraudScore > 0.5 && $fraudScore <= 0.7,
        );
    }

    private function loadModel()
    {
        // Load model from storage
        $modelPath = $this->storage->disk('models')->path('fraud/fraud_model_v2.txt');

        if (! file_exists($modelPath)) {
            // Fallback to V1 if model not found
            return null;
        }

        // In production, load actual LightGBM model
        // This is a placeholder
        return null;
    }

    private function prepareFeatures(FraudCheckDTO $dto): array
    {
        return [
            'amount' => $dto->amount,
            'is_international' => $dto->isInternational ? 1 : 0,
            'device_changed' => $dto->deviceIdChanged ? 1 : 0,
            'ip_changed' => $dto->ipAddressChanged ? 1 : 0,
            'user_age_days' => $dto->userAgeDays,
            'transaction_frequency_24h' => $dto->transactionFrequency24h,
            'avg_amount_7d' => $dto->avgAmount7d,
            // ... more features
        ];
    }

    private function predict($model, array $features): float
    {
        // In production, run actual LightGBM prediction
        // This is a placeholder implementation
        $score = 0.0;

        if ($features['amount'] > 100000) {
            $score += 0.2;
        }

        if ($features['device_changed']) {
            $score += 0.3;
        }

        if ($features['ip_changed']) {
            $score += 0.3;
        }

        if ($features['transaction_frequency_24h'] > 10) {
            $score += 0.2;
        }

        return min($score, 1.0);
    }

    private function getFraudReasons(FraudCheckDTO $dto, float $score): array
    {
        $reasons = [];

        if ($dto->amount > 100000) {
            $reasons[] = 'high_amount_v2';
        }

        if ($dto->deviceIdChanged) {
            $reasons[] = 'device_change_v2';
        }

        if ($dto->transactionFrequency24h > 10) {
            $reasons[] = 'high_frequency_v2';
        }

        return $reasons;
    }
}
