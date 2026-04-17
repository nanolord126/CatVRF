<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Services;

use App\Domains\FraudML\DTOs\PaymentFraudMLDto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * PaymentFraudMLHelper - Facade for easy integration across all verticals
 * 
 * Provides simple methods for verticals to integrate payment fraud ML
 * without needing to construct complex DTOs manually.
 * 
 * CANON 2026 - Production Ready
 */
final readonly class PaymentFraudMLHelper
{
    public function __construct(
        private PaymentFraudMLService $service,
    ) {}

    /**
     * Check payment fraud for a vertical operation
     * 
     * Simple facade method for all verticals to use
     * 
     * @param int $tenantId Tenant ID
     * @param int $userId User ID
     * @param int $amountKopecks Amount in kopecks
     * @param string $idempotencyKey Idempotency key
     * @param string $correlationId Correlation ID
     * @param string $verticalCode Vertical code (medical, food, beauty, etc.)
     * @param string $urgencyLevel Urgency level (low, medium, high, emergency)
     * @param bool $isEmergency Whether this is an emergency payment
     * @return array{decision: string, score: float, explanation: ?array}
     * @throws \RuntimeException If payment is blocked
     */
    public function checkPaymentFraud(
        int $tenantId,
        int $userId,
        int $amountKopecks,
        string $idempotencyKey,
        string $correlationId,
        string $verticalCode,
        string $urgencyLevel = 'low',
        bool $isEmergency = false,
    ): array {
        $dto = new PaymentFraudMLDto(
            tenant_id: $tenantId,
            user_id: $userId,
            operation_type: 'payment',
            amount_kopecks: $amountKopecks,
            ip_address: Request::ip(),
            device_fingerprint: Request::header('User-Agent'),
            correlation_id: $correlationId,
            idempotency_key: $idempotencyKey,
            vertical_code: $verticalCode,
            urgency_level: $urgencyLevel,
            is_emergency_payment: $isEmergency,
        );

        $result = $this->service->scorePayment($dto);

        if ($result['decision'] === 'block') {
            Log::warning('Payment blocked by ML fraud detection', [
                'vertical_code' => $verticalCode,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'amount_kopecks' => $amountKopecks,
                'score' => $result['score'],
                'explanation' => $result['explanation'],
                'correlation_id' => $correlationId,
            ]);

            throw new \RuntimeException(
                'Payment blocked by fraud detection. ' .
                'Score: ' . round($result['score'], 3) .
                '. Vertical: ' . $verticalCode .
                ($result['explanation'] ? '. Top factor: ' . array_key_first($result['explanation']['top_features'] ?? []) : '')
            );
        }

        return $result;
    }

    /**
     * Check wallet operation fraud
     * 
     * For wallet debit/credit operations
     */
    public function checkWalletFraud(
        int $tenantId,
        int $userId,
        int $walletId,
        int $amountKopecks,
        string $operationType, // 'debit' or 'credit'
        string $correlationId,
        ?string $verticalCode = null,
    ): array {
        $dto = new PaymentFraudMLDto(
            tenant_id: $tenantId,
            user_id: $userId,
            operation_type: "wallet_{$operationType}",
            amount_kopecks: $amountKopecks,
            ip_address: Request::ip(),
            device_fingerprint: Request::header('User-Agent'),
            correlation_id: $correlationId,
            idempotency_key: "wallet_{$walletId}_{$operationType}_{$correlationId}",
            vertical_code: $verticalCode ?? 'wallet',
            urgency_level: null,
            is_emergency_payment: false,
        );

        return $this->service->scorePayment($dto);
    }

    /**
     * Check medical appointment payment fraud
     * 
     * Specialized method for Medical vertical with AI diagnosis context
     */
    public function checkMedicalPaymentFraud(
        int $tenantId,
        int $userId,
        int $amountKopecks,
        string $idempotencyKey,
        string $correlationId,
        ?string $urgencyLevel = null,
        ?float $consultationPriceSpikeRatio = null,
        ?bool $isEmergency = null,
    ): array {
        $dto = new PaymentFraudMLDto(
            tenant_id: $tenantId,
            user_id: $userId,
            operation_type: 'medical_appointment_payment',
            amount_kopecks: $amountKopecks,
            ip_address: Request::ip(),
            device_fingerprint: Request::header('User-Agent'),
            correlation_id: $correlationId,
            idempotency_key: $idempotencyKey,
            vertical_code: 'medical',
            urgency_level: $urgencyLevel ?? 'low',
            is_emergency_payment: $isEmergency ?? false,
            consultation_price_spike_ratio: $consultationPriceSpikeRatio,
        );

        return $this->service->scorePayment($dto);
    }

    /**
     * Invalidate fraud check cache
     */
    public function invalidateCache(string $idempotencyKey): void
    {
        $this->service->invalidateCache($idempotencyKey);
    }
}
