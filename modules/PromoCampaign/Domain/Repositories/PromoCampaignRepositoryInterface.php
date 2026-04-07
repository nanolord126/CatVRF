<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Domain\Repositories;

use Modules\PromoCampaign\Domain\Entities\PromoCampaign;

/**
 * Абстрактный интерфейс репозитория для строгого и безопасного управления данными промо-кампаний.
 *
 * Категорически обеспечивает инверсию зависимостей (Dependency Inversion), позволяя слою приложения
 * абсолютно не зависеть от конкретной реализации механизма персистентности (базы данных).
 * Гарантирует наличие tenant-scoping в каждой реализации.
 */
interface PromoCampaignRepositoryInterface
{
    /**
     * Строго находит и извлекает промо-кампанию по ее уникальному коду и идентификатору тенанта.
     *
     * @param string $code Нормализованный промокод акции.
     * @param int $tenantId Идентификатор тенанта для строгой изоляции данных.
     * @return PromoCampaign|null Сущность кампании, если она безупречно найдена, иначе null.
     */
    public function findByCodeAndTenant(string $code, int $tenantId): ?PromoCampaign;

    /**
     * Абсолютно надежно сохраняет доменную сущность промо-кампании в адаптер базы данных.
     * Применяется как для создания, так и для обновления (например, инкремента израсходованного бюджета).
     *
     * @param PromoCampaign $campaign Полностью сформированная и консистентная сущность кампании.
     * @return void
     */
    public function save(PromoCampaign $campaign): void;

    /**
     * Исключительно надежно логгирует факт успешного или неуспешного применения промокода пользователем.
     * Соответствует канону аудита (сохранение на 3 года).
     *
     * @param string $campaignId Идентификатор примененной кампании.
     * @param int $tenantId Идентификатор тенанта.
     * @param int $userId Идентификатор пользователя.
     * @param string $action Строго типизированное действие (applied, budget_exhausted).
     * @param int $discountKopecks Сумма скидки в копейках.
     * @param string $correlationId Обязательный для аудита идентификатор корреляции.
     * @return void
     */
    public function logUsage(string $campaignId, int $tenantId, int $userId, string $action, int $discountKopecks, string $correlationId): void;
}
