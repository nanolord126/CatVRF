<?php

declare(strict_types=1);

namespace App\Domains\Finances\Domain\Interfaces;

use App\Domains\Finances\Domain\Entities\FinancialTransaction;
use App\Domains\Finances\Domain\Enums\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Контракт репозитория финансовых транзакций.
 *
 * Абстрагирует хранилище (PostgreSQL) от доменной логики.
 * Реализация — в Infrastructure\Persistence\EloquentTransactionRepository.
 *
 * Все методы работают в контексте tenant (global scope).
 * Суммы — в копейках.
 */
interface TransactionRepositoryInterface
{
    /**
     * Найти транзакцию по ID.
     */
    public function findById(int $id): ?FinancialTransaction;

    /**
     * Найти транзакцию по correlation_id.
     */
    public function findByCorrelationId(string $correlationId): ?FinancialTransaction;

    /**
     * Получить все транзакции тенанта за период.
     *
     * @return Collection<int, FinancialTransaction>
     */
    public function getForTenant(int $tenantId, CarbonImmutable $from, CarbonImmutable $to): Collection;

    /**
     * Получить транзакции тенанта за период с фильтрацией по типу.
     *
     * @param  TransactionType|null  $type  Фильтр по типу (null = все)
     * @return Collection<int, FinancialTransaction>
     */
    public function getForTenantByType(
        int $tenantId,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?TransactionType $type = null,
    ): Collection;

    /**
     * Получить сумму всех транзакций тенанта за период по типу.
     *
     * @return int Сумма в копейках
     */
    public function sumForTenantByType(
        int $tenantId,
        TransactionType $type,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): int;

    /**
     * Сохранить новую транзакцию.
     */
    public function store(FinancialTransaction $transaction): void;
}
