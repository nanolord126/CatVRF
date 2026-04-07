<?php

declare(strict_types=1);

namespace Modules\Payments\Ports;

/**
 * Outgoing Port: Платёжный шлюз.
 * Абстракция над конкретными реализациями (Tinkoff, Sber, etc.)
 */
interface PaymentGatewayPort
{
    /**
     * Инициировать платёж.
     *
     * @param  array{
     *   order_id: string,
     *   amount: int,
     *   description: string,
     *   success_url: string,
     *   fail_url: string,
     *   hold: bool,
     *   recurrent: bool,
     *   metadata: array,
     * } $payload
     * @return array{
     *   success: bool,
     *   provider_payment_id: string,
     *   payment_url: string,
     * }
     */
    public function init(array $payload): array;

    /** Получить статус платежа по ID провайдера */
    public function getStatus(string $providerPaymentId): string;

    /** Сделать возврат */
    public function refund(string $providerPaymentId, int $amountKopeks): bool;

    /** Верифицировать webhook подпись */
    public function validateWebhook(array $payload): bool;

    /** Разобрать webhook в единый формат */
    public function parseWebhook(array $payload): array;

    /** Charge recurring (рекуррентный платёж) */
    public function chargeRecurring(string $rebillId, int $amountKopeks, string $orderId): array;
}
