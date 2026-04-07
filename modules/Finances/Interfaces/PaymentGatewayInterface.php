<?php

declare(strict_types=1);

namespace Modules\Finances\Interfaces;

use Modules\Finances\Data\PaymentTransactionData;
use Modules\Finances\Enums\PaymentStatus;

/**
 * Interface PaymentGatewayInterface
 * Defines the contract for payment gateway services.
 */
interface PaymentGatewayInterface
{
    /**
     * Initialize a payment.
     *
     * @param int $tenantId The ID of the tenant initiating the payment.
     * @param int $amount Amount in kopecks.
     * @param string $orderId Unique order ID.
     * @param string $correlationId
     * @param bool $hold Whether to hold the payment (two-step payment).
     * @param array|null $meta Additional payment data.
     * @return PaymentTransactionData
     */
    public function initPayment(int $tenantId, int $amount, string $orderId, string $correlationId, bool $hold = false, ?array $meta = null): PaymentTransactionData;

    /**
     * Get the status of a payment.
     *
     * @param string $paymentId The unique identifier of the payment from the provider.
     * @param string $correlationId
     * @return PaymentStatus
     */
    public function getStatus(string $paymentId, string $correlationId): PaymentStatus;

    /**
     * Capture a previously held payment.
     *
     * @param string $paymentId The unique identifier of the payment from the provider.
     * @param int $amount The amount to capture.
     * @param string $correlationId
     * @return bool
     */
    public function capture(string $paymentId, int $amount, string $correlationId): bool;

    /**
     * Refund a payment.
     *
     * @param string $paymentId The unique identifier of the payment from the provider.
     * @param int $amount The amount to refund.
     * @param string $correlationId
     * @return bool
     */
    public function refund(string $paymentId, int $amount, string $correlationId): bool;

    /**
     * Handle an incoming webhook from the payment provider.
     *
     * @param array $payload The webhook payload.
     * @return void
     */
    public function handleWebhook(array $payload): void;

    /**
     * Fiscalize a payment (call to OFD).
     *
     * @param string $paymentId The unique identifier of the payment from the provider.
     * @param array $receiptData Receipt data for fiscalization.
     * @param string $correlationId
     * @return bool
     */
    public function fiscalize(string $paymentId, array $receiptData, string $correlationId): bool;
}
