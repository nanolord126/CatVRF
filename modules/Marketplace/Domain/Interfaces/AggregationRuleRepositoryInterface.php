<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Interfaces;

use Modules\Marketplace\Domain\Entities\AggregationRule;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Ramsey\Uuid\UuidInterface;

/**
 * Интерфейс репозитория правил агрегации
 */
interface AggregationRuleRepositoryInterface
{
    /**
     * Сохранить правило
     */
    public function save(AggregationRule $rule): void;

    /**
     * Найти по UUID
     */
    public function findByUuid(UuidInterface $uuid): ?AggregationRule;

    /**
     * Получить активные правила
     *
     * @return AggregationRule[]
     */
    public function findActive(): array;

    /**
     * Получить правила по вертикали
     *
     * @return AggregationRule[]
     */
    public function findByVertical(VerticalSource $source): array;

    /**
     * Получить правила для синхронизации
     *
     * @return AggregationRule[]
     */
    public function findPendingSync(): array;

    /**
     * Получить правила с real-time синхронизацией
     *
     * @return AggregationRule[]
     */
    public function findRealTimeRules(): array;

    /**
     * Удалить правило
     */
    public function delete(UuidInterface $uuid): void;
}
