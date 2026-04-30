<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\External;

use App\Services\FraudControlService;
use Illuminate\Http\Client\Factory as HttpClient;
use Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use Modules\Payment\Domain\ValueObjects\PaymentProvider;
use Psr\Log\LoggerInterface;

/**
 * Tinkoff Acquiring Gateway Implementation.
 */
final class TinkoffGateway implements PaymentGatewayInterface
{
    private const API_URL = 'https://securepay.tinkoff.ru/v2';

    public function __construct(
        private readonly HttpClient $http,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly string $terminalKey,
        private readonly string $secretKey,
    ) {}

    public function initPayment(
        int $amountKopecks,
        string $idempotencyKey,
        string $correlationId,
        string $description = '',
    ): array {
        $this->fraud->check(0, 'payment_init', $amountKopecks, null, null, $correlationId);

        $response = $this->http->post(self::API_URL.'/Init', [
            'TerminalKey' => $this->terminalKey,
            'Amount' => $amountKopecks,
            'OrderId' => $idempotencyKey,
            'Description' => $description,
            'Token' => $this->generateToken([
                'Amount' => $amountKopecks,
                'OrderId' => $idempotencyKey,
            ]),
        ]);

        $data = $response->json();

        $this->logger->info('Tinkoff payment initialized', [
            'payment_id' => $data['PaymentId'] ?? null,
            'correlation_id' => $correlationId,
        ]);

        return [
            'payment_id' => $data['PaymentId'] ?? '',
            'redirect_url' => $data['PaymentURL'] ?? '',
            'provider_response' => $data,
        ];
    }

    public function capture(
        string $providerPaymentId,
        int $amountKopecks,
        string $correlationId,
    ): array {
        $response = $this->http->post(self::API_URL.'/Confirm', [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $providerPaymentId,
            'Amount' => $amountKopecks,
            'Token' => $this->generateToken([
                'PaymentId' => $providerPaymentId,
                'Amount' => $amountKopecks,
            ]),
        ]);

        $data = $response->json();

        return [
            'status' => $data['Status'] ?? '',
            'provider_response' => $data,
        ];
    }

    public function refund(
        string $providerPaymentId,
        int $amountKopecks,
        string $correlationId,
    ): array {
        $response = $this->http->post(self::API_URL.'/Cancel', [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $providerPaymentId,
            'Amount' => $amountKopecks,
            'Token' => $this->generateToken([
                'PaymentId' => $providerPaymentId,
                'Amount' => $amountKopecks,
            ]),
        ]);

        $data = $response->json();

        return [
            'refund_id' => $data['PaymentId'] ?? '',
            'status' => $data['Status'] ?? '',
            'provider_response' => $data,
        ];
    }

    public function handleWebhook(
        array $payload,
        string $signature,
        string $correlationId,
    ): array {
        $this->logger->info('Tinkoff webhook received', [
            'payment_id' => $payload['PaymentId'] ?? null,
            'correlation_id' => $correlationId,
        ]);

        return [
            'payment_id' => $payload['PaymentId'] ?? '',
            'status' => $payload['Status'] ?? '',
            'amount_kopecks' => (int) ($payload['Amount'] ?? 0),
        ];
    }

    public function getProvider(): PaymentProvider
    {
        return PaymentProvider::TINKOFF;
    }

    private function generateToken(array $params): string
    {
        $params['Password'] = $this->secretKey;
        ksort($params);
        $values = array_values($params);
        $string = implode('', $values);

        return hash('sha256', $string);
    }
}
