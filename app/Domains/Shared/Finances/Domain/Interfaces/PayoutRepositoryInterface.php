<?php

declare(strict_types=1);

namespace App\Domains\Finances\Domain\Interfaces;

use App\Domains\Finances\Domain\Entities\Payout;
use App\Domains\Finances\Domain\Enums\PayoutStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Контракт репозитория выплат (payouts).
 *
 * Абстрагирует хранилище от бизнес-логики.
 * Реализация — в Infrastructure\Persistence.
 *
 * Все методы работают в контексте tenant (global scope).
 */
interface PayoutRepositoryInterface
{
    /**
     * Найти выплату по ID.
     */
    public function findById(int $id): ?Payout;

    /**
     * Найти выплату по correlation_id.
     */
    public function findByCorrelationId(string $correlationId): ?Payout;

    /**
     * Получить все выплаты тенанта.
     *
     * @return Collection<int, Payout>
     */
    public function getForTenant(int $tenantId): Collection;

    /**
     * Получить выплаты тенанта за период с опциональной фильтрацией.
     *
     * @param  PayoutStatus|null  $status  Фильтр по статусу (null = все)
     * @return Collection<int, Payout>
     */
    public function getForTenantInPeriod(
        int $tenantId,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?PayoutStatus $status = null,
    ): Collection;

    /**
     * Сохранить новую выплату.
     */
    public function store(Payout $payout): void;

    /**
     * Обновить статус выплаты.
     */
    public function updateStatus(int $id, PayoutStatus $status): void;
}
