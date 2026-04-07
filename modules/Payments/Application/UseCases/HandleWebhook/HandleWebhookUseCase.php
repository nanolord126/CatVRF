<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\HandleWebhook;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Payments\Domain\Exceptions\PaymentNotFoundException;
use Modules\Payments\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Payments\Ports\PaymentGatewayPort;

/**
 * UseCase: обработать webhook от платёжного шлюза.
 *
 * 1. Верифицировать подпись
 * 2. Найти платёж по providerPaymentId
 * 3. Обновить статус агрегата
 * 4. Сохранить + диспатч событий
 */
final class HandleWebhookUseCase
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayPort         $gateway,
    ) {}

    public function execute(HandleWebhookCommand $cmd): void
    {
        $correlationId = $cmd->correlationId;

        Log::channel('audit')->info('payment.webhook.received', [
            'correlation_id' => $correlationId,
            'gateway'        => $cmd->gatewayCode,
            'payload_keys'   => array_keys($cmd->payload),
        ]);

        // 1. Верифицировать подпись
        if (! $this->gateway->validateWebhook($cmd->payload)) {
            Log::channel('audit')->warning('payment.webhook.invalid_signature', [
                'correlation_id' => $correlationId,
                'gateway'        => $cmd->gatewayCode,
            ]);
            throw new \InvalidArgumentException('Invalid webhook signature');
        }

        // 2. Разобрать payload
        $parsed            = $this->gateway->parseWebhook($cmd->payload);
        $providerPaymentId = $parsed['provider_payment_id'] ?? '';
        $status            = strtolower($parsed['status'] ?? '');
        $reason            = $parsed['reason'] ?? 'Gateway update';

        if (empty($providerPaymentId)) {
            return;
        }

        // 3. Найти платёж по provider ID
        $payment = $this->payments->findById($parsed['order_id'] ?? $providerPaymentId);

        if ($payment === null) {
            Log::channel('audit')->warning('payment.webhook.not_found', [
                'correlation_id'     => $correlationId,
                'provider_payment_id' => $providerPaymentId,
            ]);
            return; // Молча игнорируем — возможно тестовый webhook
        }

        // 4. Обновить агрегат согласно статусу
        DB::transaction(function () use ($payment, $status, $providerPaymentId, $reason, $parsed): void {
            match ($status) {
                'confirmed', 'captured' => $payment->capture(
                    $providerPaymentId,
                    $parsed['payment_url'] ?? '',
                ),
                'rejected', 'failed', 'deadline_expired' => $payment->fail($reason),
                default => null,
            };

            $this->payments->save($payment);
        });

        // 5. Диспатч доменных событий
        foreach ($payment->pullDomainEvents() as $event) {
            event($event);
        }

        Log::channel('audit')->info('payment.webhook.processed', [
            'correlation_id' => $correlationId,
            'payment_id'     => $payment->getId(),
            'new_status'     => $payment->getStatus()->value,
        ]);
    }
}
