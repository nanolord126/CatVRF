<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use App\Domains\Payment\DTOs\CreatePaymentRecordDto;
use App\Domains\Payment\DTOs\UpdatePaymentRecordDto;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\PaymentRecord;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Координатор платежей — оркестратор внешних шлюзов.
 *
 * Последовательность: Fraud → PaymentService::create() → Gateway::initPayment()
 * → обновление записи с provider_payment_id.
 *
 * Webhook: Gateway::handleWebhook() → PaymentService::updateStatus()
 * → WalletService::credit() (через событие).
 */
final readonly class PaymentCoordinatorService
{
    public function __construct(
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private FraudControlService $fraud,
        private AuditService $audit,
        private PaymentService $paymentService,
    ) {}

    /**
     * Инициировать платёж: создать запись + вызвать шлюз.
     *
     * @return array{payment_record: PaymentRecord, redirect_url: string}
     */
    public function initPayment(
        CreatePaymentRecordDto $dto,
        PaymentGatewayInterface $gateway,
    ): array {
        $this->fraud->check($dto);

        // Идемпотентность: если запись уже есть — вернуть её
        $existing = $this->paymentService->findByIdempotencyKey($dto->idempotencyKey);

        if ($existing !== null) {
            $this->logger->info('Payment idempotency hit', [
                'idempotency_key' => $dto->idempotencyKey,
                'payment_record_id' => $existing->id,
                'correlation_id' => $dto->correlationId,
            ]);

            return [
                'payment_record' => $existing,
                'redirect_url' => '',
            ];
        }

        return $this->db->transaction(function () use ($dto, $gateway): array {
            // 1. Создаём запись в БД
            $record = $this->paymentService->create($dto);

            // 2. Вызываем шлюз
            $gatewayResult = $gateway->initPayment(
                amountKopecks: $dto->amountKopecks,
                idempotencyKey: $dto->idempotencyKey,
                correlationId: $dto->correlationId,
                description: $dto->description,
            );

            // 3. Сохраняем provider_payment_id
            $record->update([
                'provider_payment_id' => $gatewayResult['payment_id'] ?? null,
                'provider_response' => $gatewayResult['provider_response'] ?? null,
            ]);

            $this->logger->info('Payment initiated via gateway', [
                'payment_record_id' => $record->id,
                'provider' => $gateway->getProvider()->value,
                'provider_payment_id' => $gatewayResult['payment_id'] ?? null,
                'correlation_id' => $dto->correlationId,
            ]);

            $this->audit->record(
                action: 'payment_initiated',
                subjectType: PaymentRecord::class,
                subjectId: $record->id,
                newValues: [
                    'provider' => $gateway->getProvider()->value,
                    'provider_payment_id' => $gatewayResult['payment_id'] ?? null,
                ],
                correlationId: $dto->correlationId,
            );

            return [
                'payment_record' => $record->fresh(),
                'redirect_url' => $gatewayResult['redirect_url'] ?? '',
            ];
        });
    }

    /**
     * Обработать вебхук от провайдера.
     */
    public function handleWebhook(
        PaymentGatewayInterface $gateway,
        array $payload,
        string $signature,
        string $correlationId,
    ): PaymentRecord {
        $webhookResult = $gateway->handleWebhook($payload, $signature, $correlationId);

        $record = PaymentRecord::where('provider_payment_id', $webhookResult['payment_id'])->firstOrFail();

        $updateDto = new UpdatePaymentRecordDto(
            paymentRecordId: $record->id,
            status: $webhookResult['status'],
            correlationId: $correlationId,
            providerPaymentId: $webhookResult['payment_id'],
            providerResponse: $payload,
        );

        $this->logger->info('Payment webhook processed', [
            'payment_record_id' => $record->id,
            'provider' => $gateway->getProvider()->value,
            'new_status' => $webhookResult['status'],
            'correlation_id' => $correlationId,
        ]);

        return $this->paymentService->updateStatus($updateDto);
    }

    /**
     * Выполнить capture авторизованного платежа.
     */
    public function capture(
        int $paymentRecordId,
        PaymentGatewayInterface $gateway,
        string $correlationId,
    ): PaymentRecord {
        $record = PaymentRecord::findOrFail($paymentRecordId);

        $captureResult = $gateway->capture(
            providerPaymentId: $record->provider_payment_id,
            amountKopecks: $record->amount_kopecks,
            correlationId: $correlationId,
        );

        $updateDto = new UpdatePaymentRecordDto(
            paymentRecordId: $record->id,
            status: PaymentStatus::CAPTURED->value,
            correlationId: $correlationId,
            providerResponse: $captureResult['provider_response'] ?? null,
        );

        return $this->paymentService->updateStatus($updateDto);
    }

    /**
     * Выполнить возврат.
     */
    public function refund(
        int $paymentRecordId,
        PaymentGatewayInterface $gateway,
        string $correlationId,
    ): PaymentRecord {
        $this->fraud->check((object) [
            'paymentRecordId' => $paymentRecordId,
            'correlationId' => $correlationId,
        ]);

        $record = PaymentRecord::findOrFail($paymentRecordId);

        $gateway->refund(
            providerPaymentId: $record->provider_payment_id,
            amountKopecks: $record->amount_kopecks,
            correlationId: $correlationId,
        );

        $updateDto = new UpdatePaymentRecordDto(
            paymentRecordId: $record->id,
            status: PaymentStatus::REFUNDED->value,
            correlationId: $correlationId,
        );

        return $this->paymentService->updateStatus($updateDto);
    }
}
