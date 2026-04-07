<?php

declare(strict_types=1);

namespace Modules\Payments\Application\Ports;

use stdClass;

/**
 * Interface PaymentGatewayPort
 * 
 * Defines the contract for external unified payment interactions (Tinkoff, Sber, etc.).
 * Strictly belongs to the boundaries protecting the Domain layer.
 */
interface PaymentGatewayPort
{
    /**
     * Initiates a payment session with the target provider.
     * 
     * @param string $paymentId Internal identifier linking the domain payment.
     * @param int $amountKopeks Total amount in smallest currency units.
     * @param string $description Clear product or cart description for the banking gateway.
     * @param bool $recurrent Flag marking if successful payment allows tokenized rebills.
     * @param array $metadata Extra gateway specific context parameters.
     * @return object An object possessing standard fields: providerPaymentId (string) and paymentUrl (string).
     */
    public function initiatePayment(
        string $paymentId,
        int $amountKopeks,
        string $description,
        bool $recurrent,
        array $metadata
    ): object;

    /**
     * Validates incoming webhook signature or authenticity hash.
     * 
     * @param array $payload The raw decoded associative array from the gateway.
     * @param string $signature The provided signature hash.
     * @return bool True if valid, false if tampered or corrupt.
     */
    public function validateWebhook(array $payload, string $signature): bool;

    /**
     * Extracts standard standardized fields from the raw webhook payload.
     * 
     * @param array $payload Unstructured gateway array.
     * @return object Containing: internalPaymentId (string), newStatus (string), providerPaymentId (string)
     */
    public function parseWebhook(array $payload): object;

    /**
     * Queries the external status of a registered payment from the provider dynamically.
     * 
     * @param string $providerPaymentId Distinct ID registered inside the gateway.
     * @return string Normalized explicit status format fitting our Domain Enums.
     */
    public function getStatus(string $providerPaymentId): string;

    /**
     * Attempts to refund a captured payment transaction.
     * 
     * @param string $providerPaymentId Target previously charged gateway reference.
     * @param int $amountKopeks Value to subtract or refund out of original value.
     * @return bool True if successfully acknowledged by provider rules.
     */
    public function refundPayment(string $providerPaymentId, int $amountKopeks): bool;
}
