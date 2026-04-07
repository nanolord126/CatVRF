<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services\Gateways;

use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use App\Domains\Payment\Enums\PaymentProvider;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

final readonly class TinkoffGateway implements PaymentGatewayInterface
{
    public function __construct(
        private AuditService $audit,
        private LoggerInterface $logger,
        private string $terminalKey,
        private string $secretKey,
    ) {}

    public function initPayment(
        int $amountKopecks,
        string $idempotencyKey,
        string $correlationId,
        string $description = '',
    ): array {
        $this->logger->info('Tinkoff init payment called', [
            'amount_kopecks' => $amountKopecks,
            'idempotency_key' => $idempotencyKey,
            'correlation_id' => $correlationId,
        ]);

        // Mock 3rd party API call
        $mockProviderId = 'tnk_' . uniqid('', true);
        $mockUrl = 'https://securepay.tinkoff.ru/rest/Authorize/' . $mockProviderId;

        $response = [
            'payment_id' => $mockProviderId,
            'redirect_url' => $mockUrl,
            'provider_response' => [
                'Success' => true,
                'ErrorCode' => '0',
                'Message' => 'OK',
            ],
        ];

        $this->audit->log(
            action: 'tinkoff_payment_init',
            subjectType: self::class,
            subjectId: null,
            newValues: $response,
            correlationId: $correlationId,
        );

        return $response;
    }

    public function capture(
        string $providerPaymentId,
        int $amountKopecks,
        string $correlationId,
    ): array {
        $response = [
            'status' => 'CAPTURED',
            'provider_response' => [
                'PaymentId' => $providerPaymentId,
                'Amount' => $amountKopecks,
                'Status' => 'CONFIRMED',
            ],
        ];

        $this->audit->log(
            action: 'tinkoff_payment_capture',
            subjectType: self::class,
            subjectId: null,
            newValues: ['provider_id' => $providerPaymentId, 'amount' => $amountKopecks],
            correlationId: $correlationId,
        );

        return $response;
    }

    public function refund(
        string $providerPaymentId,
        int $amountKopecks,
        string $correlationId,
    ): array {
        $response = [
            'refund_id' => 'ref_' . uniqid('', true),
            'status' => 'REFUNDED',
            'provider_response' => [
                'OriginalPaymentId' => $providerPaymentId,
                'Refunded' => $amountKopecks,
                'Status' => 'COMPLETED',
            ],
        ];

        $this->audit->log(
            action: 'tinkoff_payment_refund',
            subjectType: self::class,
            subjectId: null,
            newValues: ['provider_id' => $providerPaymentId, 'refund' => $amountKopecks],
            correlationId: $correlationId,
        );

        return $response;
    }

    public function handleWebhook(
        array $payload,
        string $signature,
        string $correlationId,
    ): array {
        // Проверка подписи (Mock)
        if ($signature === 'invalid') {
            throw new \RuntimeException('Invalid signature from Tinkoff');
        }

        $paymentId = $payload['PaymentId'] ?? 'unknown';
        $status = $payload['Status'] ?? 'AUTH';
        $amount = (int) ($payload['Amount'] ?? 0);

        return [
            'payment_id' => (string) $paymentId,
            'status' => $status,
            'amount_kopecks' => $amount,
        ];
    }

    public function getProvider(): PaymentProvider
    {
        return PaymentProvider::TINKOFF;
    }
}
