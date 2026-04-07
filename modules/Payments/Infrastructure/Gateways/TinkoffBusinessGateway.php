<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Gateways;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;
use Modules\Payments\Ports\PaymentGatewayPort;

/**
 * Адаптер Tinkoff Business API.
 * Реализует PaymentGatewayPort.
 *
 * Docs: https://www.tinkoff.ru/kassa/develop/api/
 */
final class TinkoffBusinessGateway implements PaymentGatewayPort
{
    private readonly string $terminalKey;
    private readonly string $secretKey;
    private readonly string $apiUrl;

    public function __construct(
        private readonly HttpFactory $http,
    ) {
        $this->terminalKey = (string) config('payments.gateways.tinkoff.terminal_key', '');
        $this->secretKey   = (string) config('payments.gateways.tinkoff.secret_key', '');
        $this->apiUrl      = rtrim((string) config('payments.gateways.tinkoff.api_url', 'https://securepay.tinkoff.ru/v2'), '/');
    }

    public function init(array $payload): array
    {
        $body = [
            'TerminalKey' => $this->terminalKey,
            'Amount'      => $payload['amount'],
            'OrderId'     => $payload['order_id'],
            'Description' => $payload['description'] ?? 'Payment',
            'DATA'        => $payload['metadata'] ?? [],
            'Recurrent'   => $payload['recurrent'] ?? false,
            'SuccessURL'  => $payload['success_url'] ?? null,
            'FailURL'     => $payload['fail_url'] ?? null,
        ];

        if ($payload['hold'] ?? false) {
            $body['PayType'] = 'T'; // двухстадийная оплата
        }

        $body['Token'] = $this->generateToken($body);

        $response = $this->http
            ->timeout(15)
            ->retry(2, 500)
            ->post("{$this->apiUrl}/Init", $body)
            ->throw()
            ->json();

        $success = (bool) ($response['Success'] ?? false);

        Log::channel('audit')->info('tinkoff.init', [
            'order_id'   => $payload['order_id'],
            'success'    => $success,
            'payment_id' => $response['PaymentId'] ?? null,
        ]);

        return [
            'success'             => $success,
            'provider_payment_id' => (string) ($response['PaymentId'] ?? ''),
            'payment_url'         => (string) ($response['PaymentURL'] ?? ''),
        ];
    }

    public function getStatus(string $providerPaymentId): string
    {
        $body = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId'   => $providerPaymentId,
        ];
        $body['Token'] = $this->generateToken($body);

        $response = $this->http
            ->timeout(10)
            ->post("{$this->apiUrl}/GetState", $body)
            ->throw()
            ->json();

        return strtolower((string) ($response['Status'] ?? 'unknown'));
    }

    public function refund(string $providerPaymentId, int $amountKopeks): bool
    {
        $body = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId'   => $providerPaymentId,
            'Amount'      => $amountKopeks,
        ];
        $body['Token'] = $this->generateToken($body);

        $response = $this->http
            ->timeout(15)
            ->post("{$this->apiUrl}/Refund", $body)
            ->throw()
            ->json();

        return (bool) ($response['Success'] ?? false);
    }

    public function validateWebhook(array $payload): bool
    {
        if (empty($payload['Token'])) {
            return false;
        }

        $token = $payload['Token'];
        unset($payload['Token']);

        $expected = $this->generateToken($payload);
        return hash_equals($expected, $token);
    }

    public function parseWebhook(array $payload): array
    {
        return [
            'provider_payment_id' => (string) ($payload['PaymentId'] ?? ''),
            'order_id'            => (string) ($payload['OrderId'] ?? ''),
            'status'              => strtolower((string) ($payload['Status'] ?? '')),
            'amount'              => (int) ($payload['Amount'] ?? 0),
            'reason'              => (string) ($payload['Message'] ?? ''),
            'rebill_id'           => (string) ($payload['RebillId'] ?? ''),
            'payment_url'         => '',
        ];
    }

    public function chargeRecurring(string $rebillId, int $amountKopeks, string $orderId): array
    {
        $body = [
            'TerminalKey' => $this->terminalKey,
            'Amount'      => $amountKopeks,
            'OrderId'     => $orderId,
            'RebillId'    => $rebillId,
        ];
        $body['Token'] = $this->generateToken($body);

        $response = $this->http
            ->timeout(15)
            ->post("{$this->apiUrl}/Charge", $body)
            ->throw()
            ->json();

        return [
            'success'             => (bool) ($response['Success'] ?? false),
            'provider_payment_id' => (string) ($response['PaymentId'] ?? ''),
        ];
    }

    // --- Private ---

    private function generateToken(array $data): string
    {
        $data['TerminalKey'] = $this->terminalKey;
        $data['Password']    = $this->secretKey;

        ksort($data);

        $values = array_filter(
            array_values($data),
            fn ($v): bool => is_scalar($v)
        );

        return hash('sha256', implode('', $values));
    }
}
