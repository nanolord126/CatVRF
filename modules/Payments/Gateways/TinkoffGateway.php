<?php declare(strict_types=1);

namespace Modules\Payments\Gateways;

use Illuminate\Http\Client\Factory as HttpFactory;
use Modules\Payments\DTOs\GatewayInitResponse;

final class TinkoffGateway implements PaymentGatewayInterface
{
    private readonly string $terminalKey;

    private readonly string $secretKey;

    private readonly string $apiUrl;

    public function __construct(
        private readonly HttpFactory $http,
        array $config,
    ) {
        $this->terminalKey = $config['terminal_key'] ?? '';
        $this->secretKey = $config['secret_key'] ?? '';
        $this->apiUrl = rtrim((string) ($config['api_url'] ?? ''), '/');
    }

    public function init(array $payload): GatewayInitResponse
    {
        $body = [
            'TerminalKey' => $this->terminalKey,
            'Amount' => $payload['amount'],
            'OrderId' => $payload['order_id'],
            'Description' => $payload['description'] ?? 'Payment',
            'DATA' => $payload['metadata'] ?? [],
            'Recurrent' => $payload['recurrent'] ?? false,
            'SuccessURL' => $payload['success_url'] ?? null,
            'FailURL' => $payload['fail_url'] ?? null,
        ];

        $body['Token'] = $this->generateToken($body);

        $response = $this->http->post($this->apiUrl . '/Init', $body)->throw()->json();

        return new GatewayInitResponse(
            providerPaymentId: (string) ($response['PaymentId'] ?? ''),
            paymentUrl: (string) ($response['PaymentURL'] ?? ''),
            requires3ds: (bool) ($response['Needs3DS'] ?? false),
        );
    }

    public function getStatus(string $providerPaymentId): string
    {
        $body = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $providerPaymentId,
        ];
        $body['Token'] = $this->generateToken($body);

        $response = $this->http->post($this->apiUrl . '/GetState', $body)->throw()->json();

        return (string) ($response['Status'] ?? 'UNKNOWN');
    }

    public function refund(string $providerPaymentId, int $amount): bool
    {
        $body = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $providerPaymentId,
            'Amount' => $amount,
        ];
        $body['Token'] = $this->generateToken($body);

        $response = $this->http->post($this->apiUrl . '/Refund', $body)->throw()->json();

        return (bool) ($response['Success'] ?? false);
    }

    public function validateWebhook(array $payload): bool
    {
        if (empty($payload['Token'])) {
            return false;
        }

        $expected = $this->generateToken($payload);

        return hash_equals($expected, (string) $payload['Token']);
    }

    public function parseWebhook(array $payload): array
    {
        return [
            'provider_payment_id' => (string) ($payload['PaymentId'] ?? ''),
            'status' => (string) ($payload['Status'] ?? ''),
            'amount' => (int) ($payload['Amount'] ?? 0),
            'order_id' => (string) ($payload['OrderId'] ?? ''),
        ];
    }

    private function generateToken(array $params): string
    {
        unset($params['Token']);
        $params['Password'] = $this->secretKey;
        ksort($params);
        $string = implode('', $params);

        return hash('sha256', $string);
    }
}
