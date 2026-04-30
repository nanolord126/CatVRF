<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services\Gateways;

use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use App\Domains\Payment\Enums\PaymentProvider;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * СБП (Система быстрых платежей) шлюз.
 *
 * Реализует PaymentGatewayInterface для СБП через НСПК.
 * СБП работает через QR-коды и мгновенные переводы между банками.
 */
final readonly class SBPGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
        private readonly string $merchantId,
        private readonly string $secretKey,
    ) {}

    public function initPayment(
        int $amountKopecks,
        string $idempotencyKey,
        string $correlationId,
        string $description = '',
    ): array {
        $this->logger->info('SBP init payment called', [
            'amount_kopecks' => $amountKopecks,
            'idempotency_key' => $idempotencyKey,
            'correlation_id' => $correlationId,
        ]);

        // Mock SBP QR code generation
        $mockProviderId = 'sbp_'.uniqid('', true);
        $mockQrData = 'https://qr.nspk.ru/BSB'.$mockProviderId;
        $mockQrPayload = base64_encode(json_encode([
            'merchantId' => $this->merchantId,
            'amount' => $amountKopecks / 100,
            'currency' => 'RUB',
            'orderId' => $idempotencyKey,
        ]));

        $response = [
            'payment_id' => $mockProviderId,
            'redirect_url' => $mockQrData,
            'qr_payload' => $mockQrPayload,
            'provider_response' => [
                'qrCodeId' => $mockProviderId,
                'qrCodeUrl' => $mockQrData,
                'payload' => $mockQrPayload,
                'status' => 'CREATED',
            ],
        ];

        $this->audit->log(
            action: 'sbp_payment_init',
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
        // SBP - мгновенные платежи, capture не требуется
        // Но для совместимости с интерфейсом возвращаем успех
        $response = [
            'status' => 'CAPTURED',
            'provider_response' => [
                'qrCodeId' => $providerPaymentId,
                'amount' => $amountKopecks,
                'status' => 'COMPLETED',
            ],
        ];

        $this->audit->log(
            action: 'sbp_payment_capture',
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
            'refund_id' => 'sbp_ref_'.uniqid('', true),
            'status' => 'REFUNDED',
            'provider_response' => [
                'originalQrCodeId' => $providerPaymentId,
                'refunded' => $amountKopecks,
                'status' => 'COMPLETED',
            ],
        ];

        $this->audit->log(
            action: 'sbp_payment_refund',
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
        // Проверка подписи от НСПК (Mock)
        if ($signature === 'invalid') {
            throw new \RuntimeException('Invalid signature from SBP NSPK');
        }

        $paymentId = $payload['qrCodeId'] ?? 'unknown';
        $status = $payload['status'] ?? 'PENDING';
        $amount = (int) (($payload['amount'] ?? 0) * 100); // Convert from rubles to kopecks

        return [
            'payment_id' => (string) $paymentId,
            'status' => $status,
            'amount_kopecks' => $amount,
        ];
    }

    public function getProvider(): PaymentProvider
    {
        return PaymentProvider::SBP;
    }
}
