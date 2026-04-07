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
 *
 * @package App\Domains\Finances\Domain\Interfaces
 */
interface PayoutRepositoryInterface
{
    /**
     * Найти выплату по ID.
     *
     * @param int $id
     * @return Payout|null
     */
    public function findById(int $id): ?Payout;

    /**
     * Найти выплату по correlation_id.
     *
     * @param string $correlationId
     * @return Payout|null
     */
    public function findByCorrelationId(string $correlationId): ?Payout;

    /**
     * Получить все выплаты тенанта.
     *
     * @param int $tenantId
     * @return Collection<int, Payout>
     */
    public function getForTenant(int $tenantId): Collection;

    /**
     * Получить выплаты тенанта за период с опциональной фильтрацией.
     *
     * @param int              $tenantId
     * @param CarbonImmutable  $from
     * @param CarbonImmutable  $to
     * @param PayoutStatus|null $status Фильтр по статусу (null = все)
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
     *
     * @param Payout $payout
     * @return void
     */
    public function store(Payout $payout): void;

    /**
     * Обновить статус выплаты.
     *
     * @param int          $id
     * @param PayoutStatus $status
     * @return void
     */
    public function updateStatus(int $id, PayoutStatus $status): void;
}
