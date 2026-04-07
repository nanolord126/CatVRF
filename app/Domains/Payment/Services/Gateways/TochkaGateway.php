<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services\Gateways;

use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use App\Domains\Payment\Enums\PaymentProvider;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

final readonly class TochkaGateway implements PaymentGatewayInterface
{
    public function __construct(
        private AuditService $audit,
        private LoggerInterface $logger,
        private string $clientId,
        private string $clientSecret,
    ) {}

    public function initPayment(
        int $amountKopecks,
        string $idempotencyKey,
        string $correlationId,
        string $description = '',
    ): array {
        $this->logger->info('Tochka init payment called', [
            'amount_kopecks' => $amountKopecks,
            'idempotency_key' => $idempotencyKey,
        ]);

        $mockProviderId = 'tch_' . uniqid('', true);
        $mockUrl = 'https://tochka.com/api/v1/payment/' . $mockProviderId . '/pay';

        $response = [
            'payment_id' => $mockProviderId,
            'redirect_url' => $mockUrl,
            'provider_response' => [
                'paymentId' => $mockProviderId,
                'status' => 'PENDING',
                'amount' => $amountKopecks / 100, // Tochka API typically accepts float
            ],
        ];

        $this->audit->log(
            action: 'tochka_payment_init',
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
                'paymentId' => $providerPaymentId,
                'status' => 'CAPTURED',
            ],
        ];

        $this->audit->log(
            action: 'tochka_payment_capture',
            subjectType: self::class,
            subjectId: null,
            newValues: ['provider_id' => $providerPaymentId],
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
                'paymentId' => $providerPaymentId,
                'status' => 'REFUNDED',
            ],
        ];

        $this->audit->log(
            action: 'tochka_payment_refund',
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
        if ($signature === 'invalid') {
            throw new \RuntimeException('Invalid signature from Tochka');
        }

        $paymentId = $payload['paymentId'] ?? 'unknown';
        $status = $payload['status'] ?? 'COMPLETED';
        $amount = (int) (($payload['amount'] ?? 0) * 100);

        return [
            'payment_id' => (string) $paymentId,
            'status' => $status,
            'amount_kopecks' => $amount,
        ];
    }

    public function getProvider(): PaymentProvider
    {
        return PaymentProvider::TOCHKA;
    }
}
