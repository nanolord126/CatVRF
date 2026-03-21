<?php declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TochkaGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $apiKey,
    ) {}

    public function initPayment(array $data): array
    {
        Log::channel('audit')->info('Tochka: Initializing payment', [
            'amount' => $data['amount'],
            'order_id' => $data['order_id'],
        ]);

        $payload = [
            'order_id' => $data['order_id'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? '',
            'customer_email' => $data['customer_email'] ?? '',
            'return_url' => $data['return_url'] ?? '',
        ];

        $response = Http::withToken($this->apiKey)
            ->post('https://api.tochka.com/api/v1/payments', $payload);

        return $response->json();
    }

    public function capture(PaymentTransaction $transaction): bool
    {
        Log::channel('audit')->info('Tochka: Capturing payment', [
            'payment_id' => $transaction->id,
        ]);

        $response = Http::withToken($this->apiKey)
            ->post("https://api.tochka.com/api/v1/payments/{$transaction->provider_payment_id}/capture", []);

        return $response->json()['status'] === 'captured';
    }

    public function refund(PaymentTransaction $transaction, int $amount): bool
    {
        Log::channel('audit')->info('Tochka: Processing refund', [
            'payment_id' => $transaction->id,
            'amount' => $amount,
        ]);

        $response = Http::withToken($this->apiKey)
            ->post("https://api.tochka.com/api/v1/payments/{$transaction->provider_payment_id}/refund", [
                'amount' => $amount,
            ]);

        return ($response->json()['status'] ?? '') === 'refunded';
    }

    public function getStatus(string $providerPaymentId): array
    {
        return Http::withToken($this->apiKey)
            ->get("https://api.tochka.com/api/v1/payments/{$providerPaymentId}")
            ->json();
    }

    public function createPayout(array $data): array
    {
        Log::channel('audit')->info('Tochka: Creating payout', [
            'amount' => $data['amount'],
            'correlation_id' => $data['correlation_id'] ?? null,
        ]);

        return Http::withToken($this->apiKey)
            ->post('https://api.tochka.com/api/v1/payouts', [
                'order_id' => $data['order_id'],
                'amount' => $data['amount'],
                'account_number' => $data['account_number'] ?? '',
                'description' => $data['description'] ?? '',
            ])->json();
    }

    public function handleWebhook(array $payload): array
    {
        Log::channel('audit')->info('Tochka: Webhook received', [
            'order_id' => $payload['order_id'] ?? null,
            'status' => $payload['status'] ?? null,
        ]);

        return [
            'provider' => 'tochka',
            'provider_payment_id' => (string) ($payload['payment_id'] ?? ''),
            'order_id' => (string) ($payload['order_id'] ?? ''),
            'status' => $payload['status'] ?? 'unknown',
            'amount' => (int) ($payload['amount'] ?? 0),
            'raw' => $payload,
        ];
    }

    public function fiscalize(PaymentTransaction $transaction): bool
    {
        Log::channel('audit')->info('Tochka: Fiscalizing', ['payment_id' => $transaction->id]);

        // Точка использует внешний ОФД (CloudPayments / АТОЛ)
        // Реализация зависит от подключённого ОФД-провайдера
        // Здесь отправляем запрос на формирование чека
        $response = Http::withToken($this->apiKey)
            ->post("https://api.tochka.com/api/v1/payments/{$transaction->provider_payment_id}/receipt", [
                'email' => $transaction->customer_email ?? '',
                'items' => [[
                    'name' => $transaction->description ?? 'Услуга',
                    'price' => $transaction->amount,
                    'quantity' => 1,
                    'tax' => 'none',
                ]],
            ]);

        return $response->successful();
    }
}
