<?php

declare(strict_types=1);

namespace App\Domains\Shared\Payment\Gateways;

use App\Domains\Shared\Payment\Contracts\GatewayInterface;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

final readonly class TochkaBankGateway implements GatewayInterface
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('payment.tochka.api_url', 'https://enter.tochka.com/api'),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('payment.tochka.token'),
            ],
            'timeout' => 30,
        ]);
    }

    public function createPayment(array $data): array
    {
        try {
            $response = $this->client->post('invoice/create', [
                'json' => [
                    'amount' => $data['amount'],
                    'order_id' => $data['order_id'],
                    'description' => $data['description'] ?? 'B2B Оплата заказа',
                    'customer_inn' => $data['customer_inn'] ?? null,
                    'customer_name' => $data['customer_name'] ?? null,
                    'payment_deadline' => now()->addDays(14)->toIso8601String(),
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('Tochka bank payment creation failed', [
                'error' => $e->getMessage(),
                'order_id' => $data['order_id'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $response = $this->client->get("invoice/{$paymentId}/status");

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('Tochka bank payment status check failed', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function refund(string $paymentId, ?float $amount = null): array
    {
        try {
            $payload = [
                'invoice_id' => $paymentId,
            ];

            if ($amount !== null) {
                $payload['amount'] = $amount;
            }

            $response = $this->client->post('invoice/refund', [
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('Tochka bank refund failed', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function chargeRecurring(string $customerKey, float $amount, string $orderId, ?string $description = null): array
    {
        try {
            $response = $this->client->post('subscription/charge', [
                'json' => [
                    'customer_key' => $customerKey,
                    'amount' => $amount,
                    'order_id' => $orderId,
                    'description' => $description ?? 'Рекуррентный платёж B2B',
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => $result['success'] ?? false,
                'transaction_id' => $result['transaction_id'] ?? null,
                'message' => $result['message'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Tochka bank recurring charge failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
