<?php

declare(strict_types=1);

namespace App\Domains\Shared\Payment;

use App\Domains\Shared\FraudML\Services\FraudMLCoordinatorService;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * PaymentServiceAdapter - unified interface for all 28 verticals.
 *
 * Provides clean API for payment processing without exposing Payment domain internals.
 * Integrates with FraudML for fraud detection before payment creation.
 */
final readonly class PaymentServiceAdapter
{
    public function __construct(
        private readonly FraudMLCoordinatorService $fraudMLCoordinator,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Prepare payment checkout with fraud check.
     *
     * @param  array{amount: int, currency: string, user_id: int, vertical: string, sub_vertical?: string, payable_type: string, payable_id: int}  $paymentData
     * @return array{payment_intent_id: string, client_secret: string, fraud_score: float, fraud_action: string}
     */
    public function preparePayment(array $paymentData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Payment preparation started via PaymentServiceAdapter', [
            'correlation_id' => $correlationId,
            'vertical' => $paymentData['vertical'] ?? 'unknown',
            'sub_vertical' => $paymentData['sub_vertical'] ?? null,
            'amount' => $paymentData['amount'] ?? 0,
        ]);

        // Fraud ML check before payment creation
        $fraudResult = $this->fraudMLCoordinator->checkPaymentRisk([
            'amount' => $paymentData['amount'] ?? 0,
            'currency' => $paymentData['currency'] ?? 'RUB',
            'user_id' => $paymentData['user_id'] ?? null,
            'vertical' => $paymentData['vertical'] ?? 'unknown',
            'sub_vertical' => $paymentData['sub_vertical'] ?? null,
            'payable_type' => $paymentData['payable_type'] ?? null,
            'payable_id' => $paymentData['payable_id'] ?? null,
            'correlation_id' => $correlationId,
        ]);

        // Block payment if fraud score is too high
        if ($fraudResult['action'] === 'block') {
            $this->logger->warning('Payment blocked due to high fraud risk', [
                'correlation_id' => $correlationId,
                'fraud_score' => $fraudResult['score'],
                'fraud_reasons' => $fraudResult['reasons'] ?? [],
            ]);

            throw new \RuntimeException('Payment blocked due to fraud risk');
        }

        // Create payment intent (simplified - in reality delegate to Payment domain)
        $paymentIntentId = 'pi_' . Str::random(32);
        $clientSecret = 'sk_' . Str::random(64);

        $this->logger->info('Payment prepared successfully', [
            'correlation_id' => $correlationId,
            'payment_intent_id' => $paymentIntentId,
            'fraud_score' => $fraudResult['score'],
            'fraud_action' => $fraudResult['action'],
        ]);

        return [
            'payment_intent_id' => $paymentIntentId,
            'client_secret' => $clientSecret,
            'fraud_score' => $fraudResult['score'],
            'fraud_action' => $fraudResult['action'],
            'requires_additional_verification' => $fraudResult['action'] === 'challenge',
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Process payment after user confirmation.
     *
     * @param  array{payment_intent_id: string, payment_method_id: string, vertical: string}  $processData
     * @return array{payment_id: int, status: string, transaction_id: string}
     */
    public function processPayment(array $processData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Payment processing started', [
            'correlation_id' => $correlationId,
            'payment_intent_id' => $processData['payment_intent_id'] ?? null,
            'vertical' => $processData['vertical'] ?? 'unknown',
        ]);

        // Process payment (simplified - in reality delegate to Payment domain)
        $paymentId = rand(1000, 9999);
        $transactionId = 'txn_' . Str::random(32);

        $this->logger->info('Payment processed successfully', [
            'correlation_id' => $correlationId,
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
        ]);

        return [
            'payment_id' => $paymentId,
            'status' => 'succeeded',
            'transaction_id' => $transactionId,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Refund payment.
     *
     * @param  array{payment_id: int, amount?: int, reason?: string}  $refundData
     * @return array{refund_id: int, status: string}
     */
    public function refundPayment(array $refundData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Payment refund started', [
            'correlation_id' => $correlationId,
            'payment_id' => $refundData['payment_id'] ?? null,
            'amount' => $refundData['amount'] ?? null,
        ]);

        // Process refund (simplified - in reality delegate to Payment domain)
        $refundId = rand(1000, 9999);

        $this->logger->info('Payment refunded successfully', [
            'correlation_id' => $correlationId,
            'refund_id' => $refundId,
        ]);

        return [
            'refund_id' => $refundId,
            'status' => 'succeeded',
            'correlation_id' => $correlationId,
        ];
    }
}
