<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services\Gateways;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Tochka Bank Adapter - B2B payment gateway.
 *
 * Supports:
 * - B2B payments (business accounts)
 * - Mass payouts
 * - Bank transfers
 * - Account statements
 * - Currency exchange
 *
 * API Docs: https://developer.tochka.com/
 */
final readonly class TochkaBankAdapter
{
    private const string API_URL = 'https://enter.tochka.com/api/';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $apiKey,
        private readonly PendingRequest $http,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Initiate B2B payment.
     */
    public function initiatePayment(array $data): array
    {
        $payload = [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'RUB',
            'payer_account' => $data['payer_account'],
            'payee_account' => $data['payee_account'],
            'payee_inn' => $data['payee_inn'],
            'payee_name' => $data['payee_name'],
            'payment_purpose' => $data['payment_purpose'] ?? 'Payment',
            'payment_date' => $data['payment_date'] ?? now()->format('Y-m-d'),
        ];

        $response = $this->http
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post(self::API_URL.'v1/payments', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tochka payment failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Create mass payout batch.
     */
    public function createPayoutBatch(array $data): array
    {
        $payload = [
            'batch_id' => $data['batch_id'] ?? Str::uuid()->toString(),
            'payments' => $data['payments'],
            'total_amount' => array_sum(array_column($data['payments'], 'amount')),
            'currency' => $data['currency'] ?? 'RUB',
        ];

        $response = $this->http
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])
            ->timeout(60)
            ->post(self::API_URL.'v1/payouts/batch', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tochka payout batch failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Get payment status.
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $response = $this->http
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ])
            ->get(self::API_URL."v1/payments/{$paymentId}");

        if (! $response->successful()) {
            throw new \RuntimeException("Tochka get status failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Get payout batch status.
     */
    public function getPayoutBatchStatus(string $batchId): array
    {
        $response = $this->http
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ])
            ->get(self::API_URL."v1/payouts/batch/{$batchId}");

        if (! $response->successful()) {
            throw new \RuntimeException("Tochka get batch status failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Cancel payment.
     */
    public function cancelPayment(string $paymentId, string $reason = null): array
    {
        $payload = [
            'reason' => $reason ?? 'Canceled by user',
        ];

        $response = $this->http
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])
            ->post(self::API_URL."v1/payments/{$paymentId}/cancel", $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tochka cancel payment failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Get account statement.
     */
    public function getAccountStatement(array $data): array
    {
        $payload = [
            'account_number' => $data['account_number'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];

        $response = $this->http
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])
            ->post(self::API_URL.'v1/statements', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Tochka get statement failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Get exchange rates.
     */
    public function getExchangeRates(string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');

        $response = $this->http
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ])
            ->get(self::API_URL."v1/exchange-rates?date={$date}");

        if (! $response->successful()) {
            throw new \RuntimeException("Tochka get exchange rates failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Get access token (OAuth2).
     */
    private function getAccessToken(): string
    {
        // In production, cache the token with TTL
        $response = $this->http->asForm()->post(self::API_URL.'oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Tochka authentication failed');
        }

        return $response->json()['access_token'];
    }

    /**
     * Map Tochka status to internal status.
     */
    public static function mapStatus(string $tochkaStatus): string
    {
        return match ($tochkaStatus) {
            'NEW', 'PROCESSING' => 'pending',
            'EXECUTED', 'COMPLETED' => 'captured',
            'REJECTED', 'ERROR' => 'failed',
            'CANCELED' => 'cancelled',
            default => 'pending',
        };
    }
}
