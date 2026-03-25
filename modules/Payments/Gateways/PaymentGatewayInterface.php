<?php

namespace Modules\Payments\

/**
 * Class
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Class();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace Modules\Payments\Gateways
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
Gateways;

interface PaymentGatewayInterface
{
    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function createPayment(float $amount, string $orderId, array $data = []): array;
    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function checkStatus(string $paymentId): string;
    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function refund(string $paymentId, float $amount): bool;
}
