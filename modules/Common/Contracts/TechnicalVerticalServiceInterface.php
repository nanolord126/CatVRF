<?php

declare(strict_types=1);

namespace Modules\Common\Contracts;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

/**
 * Единый контракт для всех технических вертикалей платформы.
 *
 * Обязателен для: Payments, Wallet, GeoLogistics, Advertising, Analytics, Finances,
 * Delivery, Commissions, Bonuses, Loyalty и любых новых технических модулей.
 *
 * КАНОН 2026 — нарушение любого пункта считается критической ошибкой.
 */
interface TechnicalVerticalServiceInterface
{
    /**
     * Привязать сервис к конкретному тенанту.
     * Обязателен перед любой операцией с данными.
     * Использует clone (иммутабельный builder-стиль) — оригинальный объект не меняется.
     *
     * @example $service->forTenant($tenant)->deposit(...)
     */
    public function forTenant(Tenant $tenant): static;

    /**
     * Вернуть callable-скоп для Eloquent-запросов.
     * Используется в Builder::tap() или scopes.
     *
     * @example Model::query()->tap($service->getTenantScope())
     */
    public function getTenantScope(): callable;

    /**
     * Проверить, включён ли модуль для текущего тенанта.
     * Читает флаг из настроек тенанта и config/features.php.
     */
    public function isEnabled(): bool;

    /**
     * Вернуть текущий correlation_id (ленивая генерация UUID v4 при первом вызове).
     */
    public function getCorrelationId(): string;

    /**
     * Создать клон сервиса с указанным correlation_id для сквозного трейсинга.
     *
     * @example $service->withCorrelationId($request->header('X-Correlation-ID'))->charge(...)
     */
    public function withCorrelationId(string $correlationId): static;
}
