<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services\Gateways;

use App\Domains\Payment\ValueObjects\MoneyVO;
use App\Domains\Payment\ValueObjects\PaymentMethodVO;
use App\Models\PaymentTransaction;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Tinkoff Acquiring Adapter - full API integration.
 *
 * Supports:
 * - Payment initiation with 3DS
 * - Installments (Tinkoff Installments)
 * - Recurring payments
 * - GetState status checks
 * - Cancel/Refund operations
 * - Receipt fiscalization (54-ФЗ)
 * - QrCode for SBP
 * - Multi-step payments
 *
 * API Docs: https://www.tinkoff.ru/kassa/develop/api/
 */
final readonly class TinkoffAcquiringAdapter
{
    private const string API_URL = 'https://securepay.tinkoff.ru/v2/';

    public function __construct(
        private readonly string $terminalKey,
        private readonly string $secretKey,
        private readonly PendingRequest $http,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Initiate payment.
     */
    public function initiate(array $data): array
    {
        $payload = array_merge([
            'TerminalKey' => $this->terminalKey,
            'Token' => $this->generateToken($data),
        ], $data);

        $response = $this->http->timeout(15)
            ->post(self::API_URL.'Init', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tinkoff Init failed: {$response->status()}");
        }

        $result = $response->json();

        if (! ($result['Success'] ?? false)) {
            $this->logger->error('Tinkoff Init returned failure', [
                'message' => $result['Message'] ?? 'Unknown error',
                'error_code' => $result['ErrorCode'] ?? 'Unknown',
            ]);

            throw new \RuntimeException($result['Message'] ?? 'Payment initiation failed');
        }

        return $result;
    }

    /**
     * Initiate payment with 3DS support.
     */
    public function initiateWith3DS(array $data): array
    {
        $data['DATA'] = json_encode([
            'IP' => $data['ip'] ?? request()->ip(),
            'DeviceFingerprint' => $data['device_fingerprint'] ?? null,
        ]);

        return $this->initiate($data);
    }

    /**
     * Initiate installment payment (Tinkoff Installments).
     */
    public function initiateInstallments(array $data): array
    {
        $payload = array_merge($data, [
            'PayType' => 'O', // One-step payment
            'Receipt' => $this->buildReceipt($data),
        ]);

        return $this->initiate($payload);
    }

    /**
     * Initiate recurring payment.
     */
    public function initiateRecurring(array $data): array
    {
        $payload = array_merge($data, [
            'Recurrent' => 'Y',
            'CustomerKey' => $data['customer_key'] ?? throw new \InvalidArgumentException('customer_key is required for recurring'),
        ]);

        return $this->initiate($payload);
    }

    /**
     * Get payment status.
     */
    public function getState(string $paymentId): array
    {
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $paymentId,
            'Token' => $this->generateTokenForPayment($paymentId),
        ];

        $response = $this->http->post(self::API_URL.'GetState', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tinkoff GetState failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Confirm (capture) payment.
     */
    public function confirm(string $paymentId, ?int $amount = null): array
    {
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $paymentId,
            'Token' => $this->generateTokenForPayment($paymentId),
        ];

        if ($amount !== null) {
            $payload['Amount'] = $amount;
        }

        $response = $this->http->post(self::API_URL.'Confirm', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tinkoff Confirm failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Cancel payment.
     */
    public function cancel(string $paymentId, ?int $amount = null): array
    {
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $paymentId,
            'Token' => $this->generateTokenForPayment($paymentId),
        ];

        if ($amount !== null) {
            $payload['Amount'] = $amount;
        }

        $response = $this->http->post(self::API_URL.'Cancel', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tinkoff Cancel failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Refund payment.
     */
    public function refund(string $paymentId, int $amount, ?string $receiptData = null): array
    {
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $paymentId,
            'Amount' => $amount,
            'Token' => $this->generateTokenForPayment($paymentId),
        ];

        if ($receiptData !== null) {
            $payload['Receipt'] = $receiptData;
        }

        $response = $this->http->post(self::API_URL.'Refund', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tinkoff Refund failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Send receipt for fiscalization (54-ФЗ).
     */
    public function sendReceipt(array $receiptData): array
    {
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'Token' => $this->generateTokenForReceipt($receiptData),
            'Receipt' => $receiptData,
        ];

        $response = $this->http->post(self::API_URL.'SendReceipt', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tinkoff SendReceipt failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Get QR code for SBP payment.
     */
    public function getQr(array $data): array
    {
        $payload = array_merge([
            'TerminalKey' => $this->terminalKey,
            'Token' => $this->generateToken($data),
            'DataType' => 'PAYLOAD',
        ], $data);

        $response = $this->http->post(self::API_URL.'GetQr', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tinkoff GetQr failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Add customer card for recurring payments.
     */
    public function addCard(array $data): array
    {
        $payload = array_merge([
            'TerminalKey' => $this->terminalKey,
            'Token' => $this->generateToken($data),
            'CustomerKey' => $data['customer_key'] ?? throw new \InvalidArgumentException('customer_key is required'),
        ], $data);

        $response = $this->http->post(self::API_URL.'AddCard', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tinkoff AddCard failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Get customer cards.
     */
    public function getCards(string $customerKey): array
    {
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'CustomerKey' => $customerKey,
            'Token' => $this->generateToken(['CustomerKey' => $customerKey]),
        ];

        $response = $this->http->post(self::API_URL.'GetCardList', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tinkoff GetCardList failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Remove customer card.
     */
    public function removeCard(string $cardId, string $customerKey): array
    {
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'CardId' => $cardId,
            'CustomerKey' => $customerKey,
            'Token' => $this->generateToken(['CardId' => $cardId, 'CustomerKey' => $customerKey]),
        ];

        $response = $this->http->post(self::API_URL.'RemoveCard', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tinkoff RemoveCard failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Build receipt for fiscalization.
     */
    private function buildReceipt(array $data): array
    {
        return [
            'Email' => $data['customer_email'] ?? null,
            'Phone' => $data['customer_phone'] ?? null,
            'Taxation' => $data['taxation'] ?? 'osn',
            'Items' => $data['items'] ?? [],
        ];
    }

    /**
     * Generate token for payment initiation.
     */
    private function generateToken(array $data): string
    {
        $values = [
            $data['Amount'] ?? '',
            $data['OrderId'] ?? '',
            $this->terminalKey,
            $data['Password'] ?? '',
            $data['Description'] ?? '',
        ];

        $tokenString = implode('', array_filter($values)).$this->secretKey;

        return hash('sha256', $tokenString);
    }

    /**
     * Generate token for existing payment.
     */
    private function generateTokenForPayment(string $paymentId): string
    {
        return hash('sha256', $this->terminalKey.$paymentId.$this->secretKey);
    }

    /**
     * Generate token for receipt.
     */
    private function generateTokenForReceipt(array $receipt): string
    {
        $receiptString = json_encode($receipt);

        return hash('sha256', $this->terminalKey.$receiptString.$this->secretKey);
    }

    /**
     * Map Tinkoff status to internal status.
     */
    public static function mapStatus(string $tinkoffStatus): string
    {
        return match ($tinkoffStatus) {
            'AUTHORIZED' => 'authorized',
            'CONFIRMED' => 'captured',
            'REVERSED', 'CANCELED' => 'cancelled',
            'REFUNDED', 'PARTIAL_REFUNDED' => 'refunded',
            'REJECTED' => 'failed',
            default => 'pending',
        };
    }
}
