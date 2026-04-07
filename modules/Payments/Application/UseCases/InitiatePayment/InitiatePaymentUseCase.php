<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\InitiatePayment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Payments\Domain\Entities\Payment;
use Modules\Payments\Domain\Exceptions\DuplicatePaymentException;
use Modules\Payments\Domain\Repositories\IdempotencyRepositoryInterface;
use Modules\Payments\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Payments\Domain\ValueObjects\IdempotencyKey;
use Modules\Payments\Domain\ValueObjects\Money;
use Modules\Payments\Ports\FraudCheckPort;
use Modules\Payments\Ports\PaymentGatewayPort;

/**
 * UseCase: Инициировать платёж.
 *
 * Слой Application — оркестрирует доменные объекты и порты.
 * Fraud-check → Idempotency → Gateway.init → persist → events dispatch.
 */
final class InitiatePaymentUseCase
{
    public function __construct(
        private readonly PaymentRepositoryInterface    $payments,
        private readonly IdempotencyRepositoryInterface $idempotency,
        private readonly PaymentGatewayPort            $gateway,
        private readonly FraudCheckPort                $fraud,
    ) {}

    public function execute(InitiatePaymentCommand $cmd): InitiatePaymentResult
    {
        $correlationId   = $cmd->correlationId;
        $idempotencyKey  = IdempotencyKey::fromString($cmd->idempotencyKey);
        $operation       = 'payment.initiate';

        Log::channel('audit')->info('payment.initiate.start', [
            'correlation_id' => $correlationId,
            'tenant_id'      => $cmd->tenantId,
            'user_id'        => $cmd->userId,
            'amount'         => $cmd->amountKopeks,
            'idempotency'    => $cmd->idempotencyKey,
        ]);

        // 1. Fraud check — ПЕРЕД любой мутацией
        $this->fraud->check(
            userId:        $cmd->userId,
            operationType: $operation,
            amount:        $cmd->amountKopeks,
            context:       [
                'tenant_id'      => $cmd->tenantId,
                'correlation_id' => $correlationId,
            ],
        );

        // 2. Idempotency check — вернуть кэшированный ответ если повтор
        if ($this->idempotency->exists($cmd->tenantId, $operation, $idempotencyKey)) {
            $cached = $this->idempotency->getResponse($cmd->tenantId, $operation, $idempotencyKey);

            // Параллельно проверяем — нет ли уже платежа в БД
            $existing = $this->payments->findByIdempotencyKey($cmd->tenantId, $idempotencyKey);
            if ($existing !== null) {
                Log::channel('audit')->info('payment.initiate.idempotent', [
                    'correlation_id' => $correlationId,
                    'existing_id'    => $existing->getId(),
                ]);

                return new InitiatePaymentResult(
                    paymentId:    $existing->getId(),
                    paymentUrl:   $existing->getPaymentUrl() ?? $cached['payment_url'] ?? '',
                    status:       $existing->getStatus()->value,
                    isDuplicate:  true,
                    correlationId: $correlationId,
                );
            }
        }

        // 3. Создать агрегат и вызвать gateway + сохранить в одной транзакции
        $paymentId  = Str::uuid()->toString();
        $money      = Money::ofKopeks($cmd->amountKopeks, $cmd->currency);
        $payment    = Payment::initiate(
            id:             $paymentId,
            tenantId:       $cmd->tenantId,
            userId:         $cmd->userId,
            amount:         $money,
            idempotencyKey: $idempotencyKey,
            correlationId:  $correlationId,
            metadata:       $cmd->metadata,
            recurring:      $cmd->recurring,
        );

        // 4. Вызвать gateway (вне транзакции — сетевой вызов)
        $gatewayResponse = $this->gateway->init([
            'order_id'    => $paymentId,
            'amount'      => $cmd->amountKopeks,
            'description' => $cmd->description,
            'success_url' => $cmd->successUrl,
            'fail_url'    => $cmd->failUrl,
            'hold'        => $cmd->hold,
            'recurrent'   => $cmd->recurring,
            'metadata'    => array_merge($cmd->metadata, [
                'tenant_id'      => $cmd->tenantId,
                'user_id'        => $cmd->userId,
                'correlation_id' => $correlationId,
            ]),
        ]);

        // 5. Обновить агрегат (URL получен от gateway)
        if ($gatewayResponse['success'] ?? false) {
            $payment->capture(
                $gatewayResponse['provider_payment_id'],
                $gatewayResponse['payment_url'] ?? ''
            );
        }

        // 6. Сохранить + idempotency в транзакции
        DB::transaction(function () use ($payment, $cmd, $operation, $idempotencyKey, $gatewayResponse, $paymentId, $correlationId): void {
            $this->payments->save($payment);

            $this->idempotency->store(
                tenantId:    $cmd->tenantId,
                operation:   $operation,
                key:         $idempotencyKey,
                response:    [
                    'payment_id'  => $paymentId,
                    'payment_url' => $gatewayResponse['payment_url'] ?? '',
                ],
                payloadHash: hash('sha256', json_encode([
                    $cmd->amountKopeks,
                    $cmd->currency,
                    $cmd->idempotencyKey,
                ])),
                expiresAt: new \DateTimeImmutable('+24 hours'),
            );
        });

        // 7. Диспатч доменных событий
        foreach ($payment->pullDomainEvents() as $event) {
            event($event);
        }

        Log::channel('audit')->info('payment.initiate.success', [
            'correlation_id' => $correlationId,
            'payment_id'     => $paymentId,
            'payment_url'    => $gatewayResponse['payment_url'] ?? '',
        ]);

        return new InitiatePaymentResult(
            paymentId:    $paymentId,
            paymentUrl:   $gatewayResponse['payment_url'] ?? '',
            status:       $payment->getStatus()->value,
            isDuplicate:  false,
            correlationId: $correlationId,
        );
    }
}
