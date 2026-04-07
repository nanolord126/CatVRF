<?php

declare(strict_types=1);

namespace App\Domains\Payment\Contracts;

use App\Domains\Payment\Enums\PaymentProvider;

/**
 * Интерфейс для платёжных шлюзов.
 *
 * Каждый провайдер (Tinkoff, Sber, Tochka и т.д.) должен реализовать
 * этот контракт. Координатор PaymentCoordinatorService выбирает
 * нужный драйвер через DI / Factory.
 */
interface PaymentGatewayInterface
{
    /**
     * Инициировать платёж (создать сессию у провайдера).
     *
     * @param int    $amountKopecks  сумма в копейках
     * @param string $idempotencyKey ключ идемпотентности
     * @param string $correlationId  correlation_id для аудита
     * @param string $description    описание платежа
     *
     * @return array{payment_id: string, redirect_url: string, provider_response: array<string, mixed>}
     */
    public function initPayment(
        int $amountKopecks,
        string $idempotencyKey,
        string $correlationId,
        string $description = '',
    ): array;

    /**
     * Подтвердить (capture) ранее авторизованный платёж.
     *
     * @param string $providerPaymentId идентификатор у провайдера
     * @param int    $amountKopecks     сумма к подтверждению
     * @param string $correlationId     correlation_id для аудита
     *
     * @return array{status: string, provider_response: array<string, mixed>}
     */
    public function capture(
        string $providerPaymentId,
        int $amountKopecks,
        string $correlationId,
    ): array;

    /**
     * Выполнить возврат (полный или частичный).
     *
     * @param string $providerPaymentId идентификатор у провайдера
     * @param int    $amountKopecks     сумма возврата
     * @param string $correlationId     correlation_id для аудита
     *
     * @return array{refund_id: string, status: string, provider_response: array<string, mixed>}
     */
    public function refund(
        string $providerPaymentId,
        int $amountKopecks,
        string $correlationId,
    ): array;

    /**
     * Обработать вебхук от провайдера.
     *
     * @param array<string, mixed> $payload     тело запроса
     * @param string               $signature   подпись от провайдера
     * @param string               $correlationId correlation_id
     *
     * @return array{payment_id: string, status: string, amount_kopecks: int}
     */
    public function handleWebhook(
        array $payload,
        string $signature,
        string $correlationId,
    ): array;

    /**
     * Провайдер, реализующий этот шлюз.
     */
    public function getProvider(): PaymentProvider;
}
