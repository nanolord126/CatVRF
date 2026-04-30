<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\DTOs\CreatePaymentRecordDto;
use App\Domains\Payment\Models\PaymentRecord;

/**
 * PaymentServiceAdapter - Adapter for gradual migration to new PaymentEngine.
 *
 * This adapter allows gradual rollout of the new payment architecture
 * using feature flags. Once migration is complete, this can be removed.
 */
final readonly class PaymentServiceAdapter
{
    public function __construct(
        private readonly PaymentEngineService $newEngine,
        private readonly PaymentService $legacyService,
    ) {}

    /**
     * Create payment using either new or legacy engine based on feature flag.
     */
    public function createPayment(CreatePaymentRecordDto $dto, string $returnUrl): PaymentRecord
    {
        if (config('payment.features.new_engine_enabled', false)) {
            return $this->newEngine->createPayment($dto, $returnUrl);
        }

        return $this->legacyService->create($dto);
    }

    /**
     * Capture payment using either new or legacy engine.
     */
    public function capturePayment(int $paymentId, string $correlationId): PaymentRecord
    {
        if (config('payment.features.new_engine_enabled', false)) {
            return $this->newEngine->capturePayment($paymentId, $correlationId);
        }

        // Legacy capture logic would go here
        // For now, delegate to legacy service
        return $this->legacyService->findById($paymentId) ?? throw new \InvalidArgumentException('Payment not found');
    }

    /**
     * Refund payment using either new or legacy engine.
     */
    public function refundPayment(int $paymentId, ?int $amountKopecks, string $correlationId): PaymentRecord
    {
        if (config('payment.features.new_engine_enabled', false)) {
            return $this->newEngine->refundPayment($paymentId, $amountKopecks, $correlationId);
        }

        // Legacy refund logic would go here
        return $this->legacyService->findById($paymentId) ?? throw new \InvalidArgumentException('Payment not found');
    }
}
