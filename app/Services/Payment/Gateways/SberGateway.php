<?php declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SberGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly string $merchantId,
    ) {}

    public function initPayment(array $data): array
    {
        Log::channel('audit')->info('Sber: Initializing payment', [
            'amount' => $data['amount'],
            'order_id' => $data['order_id'],
        ]);

        $url = 'https://3dsec.sberbank.ru/payment/rest/registerOrder.do';

        $response = Http::asForm()->post($url, [
            'userName' => $this->username,
            'password' => $this->password,
            'orderNumber' => $data['order_id'],
            'amount' => $data['amount'],
            'currency' => '810', // RUB
            'returnUrl' => $data['return_url'] ?? '',
            'description' => $data['description'] ?? '',
            'clientId' => $data['customer_id'] ?? '',
            'email' => $data['customer_email'] ?? '',
        ]);

        return $response->json();
    }

    public function capture(PaymentTransaction $transaction): bool
    {
        Log::channel('audit')->info('Sber: Capturing payment', [
            'payment_id' => $transaction->id,
        ]);

        $url = 'https://3dsec.sberbank.ru/payment/rest/deposit.do';

        $response = Http::asForm()->post($url, [
            'userName' => $this->username,
            'password' => $this->password,
            'orderId' => $transaction->provider_payment_id,
            'amount' => $transaction->amount,
        ]);

        return $response->json()['errorCode'] === '0';
    }

    public function refund(PaymentTransaction $transaction, int $amount): bool
    {
        Log::channel('audit')->info('Sber: Processing refund', [
            'payment_id' => $transaction->id,
            'amount' => $amount,
        ]);

        $url = 'https://3dsec.sberbank.ru/payment/rest/refund.do';

        $response = Http::asForm()->post($url, [
            'userName' => $this->username,
            'password' => $this->password,
            'orderId' => $transaction->provider_payment_id,
            'amount' => $amount,
        ]);

        return ($response->json()['errorCode'] ?? '') === '0';
    }

    public function getStatus(string $providerPaymentId): array
    {
        $response = Http::asForm()->post('https://3dsec.sberbank.ru/payment/rest/getOrderStatusExtended.do', [
            'userName' => $this->username,
            'password' => $this->password,
            'orderId' => $providerPaymentId,
        ]);

        return $response->json();
    }

    public function createPayout(array $data): array
    {
        Log::channel('audit')->info('Sber: Creating payout', [
            'amount' => $data['amount'],
            'correlation_id' => $data['correlation_id'] ?? null,
        ]);

        $response = Http::asForm()->post('https://3dsec.sberbank.ru/payment/rest/payout.do', [
            'userName' => $this->username,
            'password' => $this->password,
            'merchantId' => $this->merchantId,
            'orderNumber' => $data['order_id'],
            'amount' => $data['amount'],
            'clientId' => $data['customer_id'] ?? '',
        ]);

        return $response->json();
    }

    public function handleWebhook(array $payload): array
    {
        Log::channel('audit')->info('Sber: Webhook received', [
            'order_id' => $payload['orderNumber'] ?? null,
            'status' => $payload['orderStatus'] ?? null,
        ]);

        $statusMap = [
            0 => 'pending',
            1 => 'pending',
            2 => 'captured',
            3 => 'failed',
            4 => 'refunded',
            6 => 'refunded',
        ];

        return [
            'provider' => 'sber',
            'provider_payment_id' => (string) ($payload['orderId'] ?? ''),
            'order_id' => (string) ($payload['orderNumber'] ?? ''),
            'status' => $statusMap[$payload['orderStatus'] ?? -1] ?? 'unknown',
            'amount' => (int) ($payload['amount'] ?? 0),
            'raw' => $payload,
        ];
    }

    public function fiscalize(PaymentTransaction $transaction): bool
    {
        // Сбер использует собственный ОФД-модуль через tax_system параметр при initPayment
        // Для 54-ФЗ закрывающий чек отправляется через sendReceipt
        Log::channel('audit')->info('Sber: Fiscalizing', ['payment_id' => $transaction->id]);

        $response = Http::asForm()->post('https://3dsec.sberbank.ru/payment/rest/sendReceipt.do', [
            'userName' => $this->username,
            'password' => $this->password,
            'orderId' => $transaction->provider_payment_id,
            'receipt' => json_encode([
                'email' => $transaction->customer_email ?? '',
                'items' => [[
                    'name' => $transaction->description ?? 'Услуга',
                    'price' => $transaction->amount,
                    'quantity' => 1,
                    'amount' => $transaction->amount,
                    'tax' => 'none',
                ]],
            ]),
        ]);

        return ($response->json()['errorCode'] ?? '') === '0';
    }
}
