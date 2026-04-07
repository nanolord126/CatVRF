<?php declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 */


namespace App\Services\Payment\Gateways;

use App\Models\PaymentTransaction;

interface PaymentGatewayInterface
{
    /**
     * Инициирует платёж (с холдом или без).
     */
    public function initPayment(array $data): array;

    /**
     * Возвращает статус платежа у провайдера.
     */
    public function getStatus(string $providerPaymentId): array;

    /**
     * Подтверждает холд (Capture).
     */
    public function capture(PaymentTransaction $transaction): bool;

    /**
     * Возврат средств.
     */
    public function refund(PaymentTransaction $transaction, int $amount): bool;

    /**
     * Массовая выплата (payout). Для B2B-выплат бизнесу.
     */
    public function createPayout(array $data): array;

    /**
     * Обработка webhook от провайдера. Возвращает распознанный статус.
     */
    public function handleWebhook(array $payload): array;

    /**
     * ОФД-фискализация (54-ФЗ). Вызывается только после captured.
     */
    public function fiscalize(PaymentTransaction $transaction): bool;
}
