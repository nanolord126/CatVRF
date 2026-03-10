<?php

namespace Modules\Payments\Gateways;

use Illuminate\Support\Facades\Http;

class TinkoffGateway implements PaymentGatewayInterface
{
    protected string $terminalId;
    protected string $secretKey;
    protected string $apiUrl;

    public function __construct(array $config)
    {
        $this->terminalId = $config['terminal_id'];
        $this->secretKey = $config['secret_key'];
        $this->apiUrl = $config['api_url'];
    }

    public function createPayment(float $amount, string $orderId, array $data = []): array
    {
        $payload = [
            'TerminalKey' => $this->terminalId,
            'Amount' => (int) ($amount * 100),
            'OrderId' => $orderId,
            'Data' => $data,
        ];

        $payload['Token'] = $this->generateToken($payload);

        $response = Http::post($this->apiUrl . 'Init', $payload);

        return $response->json();
    }

    public function checkStatus(string $paymentId): string
    {
        $payload = [
            'TerminalKey' => $this->terminalId,
            'PaymentId' => $paymentId,
        ];

        $payload['Token'] = $this->generateToken($payload);

        $response = Http::post($this->apiUrl . 'GetState', $payload);

        return $response->json()['Status'] ?? 'UNKNOWN';
    }

    public function refund(string $paymentId, float $amount): bool
    {
        $payload = [
            'TerminalKey' => $this->terminalId,
            'PaymentId' => $paymentId,
            'Amount' => (int) ($amount * 100),
        ];

        $payload['Token'] = $this->generateToken($payload);

        $response = Http::post($this->apiUrl . 'Refund', $payload);

        return $response->json()['Success'] ?? false;
    }

    protected function generateToken(array $params): string
    {
        $params['Password'] = $this->secretKey;
        ksort($params);
        $string = implode('', $params);
        return hash('sha256', $string);
    }
}
