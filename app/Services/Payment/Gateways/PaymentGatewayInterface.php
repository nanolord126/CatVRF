<?php declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\PaymentTransaction;

/**
 * Единый интерфейс платёжного шлюза (КАНОН 2026).
 * Все реализации (Tinkoff, Tochka, Sber, SBP) обязаны имплементировать все методы.
 */
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
