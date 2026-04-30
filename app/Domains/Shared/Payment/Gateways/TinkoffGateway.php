<?php

declare(strict_types=1);

namespace App\Domains\Shared\Payment\Gateways;

use App\Domains\Shared\Payment\Contracts\GatewayInterface;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

final readonly class TinkoffGateway implements GatewayInterface
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('payment.tinkoff.api_url', 'https://securepay.tinkoff.ru/v2'),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    public function createPayment(array $data): array
    {
        try {
            $response = $this->client->post('Init', [
                'json' => [
                    'TerminalKey' => config('payment.tinkoff.terminal_key'),
                    'Amount' => $data['amount'] * 100, // в копейках
                    'OrderId' => $data['order_id'],
                    'Description' => $data['description'] ?? 'Оплата заказа',
                    'CustomerKey' => $data['customer_key'] ?? $data['order_id'],
                    'DATA' => [
                        'vertical' => $data['vertical'] ?? 'supermarket',
                        'is_b2b' => $data['is_b2b'] ?? false,
                    ],
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('Tinkoff payment creation failed', [
                'error' => $e->getMessage(),
                'order_id' => $data['order_id'] ?? null,
            ]);

            return [
                'Success' => false,
                'Message' => $e->getMessage(),
            ];
        }
    }

    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $response = $this->client->post('GetState', [
                'json' => [
                    'TerminalKey' => config('payment.tinkoff.terminal_key'),
                    'PaymentId' => $paymentId,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('Tinkoff payment status check failed', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
            ]);

            return [
                'Success' => false,
                'Message' => $e->getMessage(),
            ];
        }
    }

    public function refund(string $paymentId, ?float $amount = null): array
    {
        try {
            $payload = [
                'TerminalKey' => config('payment.tinkoff.terminal_key'),
                'PaymentId' => $paymentId,
            ];

            if ($amount !== null) {
                $payload['Amount'] = $amount * 100; // в копейках
            }

            $response = $this->client->post('Cancel', [
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('Tinkoff refund failed', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
            ]);

            return [
                'Success' => false,
                'Message' => $e->getMessage(),
            ];
        }
    }

    public function chargeRecurring(string $customerKey, float $amount, string $orderId, ?string $description = null): array
    {
        try {
            $response = $this->client->post('Charge', [
                'json' => [
                    'TerminalKey' => config('payment.tinkoff.terminal_key'),
                    'Amount' => $amount * 100,
                    'OrderId' => $orderId,
                    'CustomerKey' => $customerKey,
                    'Description' => $description ?? 'Рекуррентный платёж',
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => $result['Success'] ?? false,
                'transaction_id' => $result['PaymentId'] ?? null,
                'message' => $result['Message'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Tinkoff recurring charge failed', [
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
