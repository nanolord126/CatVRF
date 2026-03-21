<?php declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TinkoffGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $terminalKey,
        private readonly string $secretKey,
    ) {}

    public function initPayment(array $data): array
    {
        Log::channel('audit')->info('Tinkoff: Initializing payment', [
            'amount' => $data['amount'],
            'order_id' => $data['order_id'],
        ]);

        $payload = [
            'TerminalKey' => $this->terminalKey,
            'Amount' => $data['amount'],
            'OrderId' => $data['order_id'],
            'Description' => $data['description'] ?? '',
            'CustomerKey' => $data['customer_key'] ?? '',
            'Recurrent' => $data['recurrent'] ?? false,
            'Token' => $this->generateToken($data),
        ];

        $response = Http::post('https://securepay.tinkoff.ru/v2/Init', $payload);

        return $response->json();
    }

    public function capture(PaymentTransaction $transaction): bool
    {
        Log::channel('audit')->info('Tinkoff: Capturing payment', [
            'payment_id' => $transaction->id,
            'provider_payment_id' => $transaction->provider_payment_id,
        ]);

        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $transaction->provider_payment_id,
            'Token' => $this->generateTokenForPayment($transaction->provider_payment_id),
        ];

        $response = Http::post('https://securepay.tinkoff.ru/v2/Confirm', $payload);

        return $response->json()['Success'] ?? false;
    }

    public function refund(PaymentTransaction $transaction, int $amount): bool
    {
        Log::channel('audit')->info('Tinkoff: Processing refund', [
            'payment_id' => $transaction->id,
            'amount' => $amount,
        ]);

        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $transaction->provider_payment_id,
            'Amount' => $amount,
            'Token' => $this->generateTokenForPayment($transaction->provider_payment_id),
        ];

        $response = Http::post('https://securepay.tinkoff.ru/v2/Refund', $payload);

        return $response->json()['Success'] ?? false;
    }

    public function getStatus(string $providerPaymentId): array
    {
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $providerPaymentId,
            'Token' => $this->generateTokenForPayment($providerPaymentId),
        ];

        return Http::post('https://securepay.tinkoff.ru/v2/GetState', $payload)->json();
    }

    public function createPayout(array $data): array
    {
        Log::channel('audit')->info('Tinkoff: Creating payout', [
            'amount' => $data['amount'],
            'correlation_id' => $data['correlation_id'] ?? null,
        ]);

        $payload = [
            'TerminalKey' => $this->terminalKey,
            'Amount' => $data['amount'],
            'OrderId' => $data['order_id'],
            'Description' => $data['description'] ?? '',
            'AccountNumber' => $data['account_number'] ?? '',
            'Token' => $this->generateToken($data),
        ];

        return Http::post('https://securepay.tinkoff.ru/v2/Payout', $payload)->json();
    }

    public function handleWebhook(array $payload): array
    {
        Log::channel('audit')->info('Tinkoff: Webhook received', [
            'order_id' => $payload['OrderId'] ?? null,
            'status' => $payload['Status'] ?? null,
        ]);

        $status = match ($payload['Status'] ?? '') {
            'AUTHORIZED' => 'authorized',
            'CONFIRMED' => 'captured',
            'REFUNDED', 'PARTIAL_REFUNDED' => 'refunded',
            'REJECTED', 'REVERSED', 'CANCELLED' => 'failed',
            default => 'unknown',
        };

        return [
            'provider' => 'tinkoff',
            'provider_payment_id' => (string) ($payload['PaymentId'] ?? ''),
            'order_id' => (string) ($payload['OrderId'] ?? ''),
            'status' => $status,
            'amount' => (int) ($payload['Amount'] ?? 0),
            'raw' => $payload,
        ];
    }

    public function fiscalize(PaymentTransaction $transaction): bool
    {
        Log::channel('audit')->info('Tinkoff: Fiscalizing', ['payment_id' => $transaction->id]);

        // ОФД через Tinkoff Касса (54-ФЗ)
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $transaction->provider_payment_id,
            'Receipt' => [
                'Email' => $transaction->customer_email ?? '',
                'Taxation' => 'osn',
                'Items' => [
                    [
                        'Name' => $transaction->description ?? 'Услуга',
                        'Quantity' => 1,
                        'Amount' => $transaction->amount,
                        'Price' => $transaction->amount,
                        'Tax' => 'none',
                    ],
                ],
            ],
            'Token' => $this->generateTokenForPayment($transaction->provider_payment_id),
        ];

        $response = Http::post('https://securepay.tinkoff.ru/v2/SendClosingReceipt', $payload);

        return $response->json()['Success'] ?? false;
    }

    private function generateToken(array $data): string
    {
        $tokenString = $data['order_id'] . $data['amount'] . $this->secretKey;

        return md5($tokenString);
    }

    private function generateTokenForPayment(string $paymentId): string
    {
        $tokenString = $this->terminalKey . $paymentId . $this->secretKey;

        return md5($tokenString);
    }
}
