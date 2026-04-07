<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Domain\Repositories;

use Modules\AIConstructor\Domain\Entities\AIConstruction;
use Illuminate\Support\Collection;

/**
 * Исключительно абстрактный интерфейс паттерна Репозиторий для персистентности AI-генераций.
 *
 * Категорически обеспечивает инверсию зависимости (DIP) между ядром предметной области
 * и конкретным механизмом хранения (PostgreSQL, ClickHouse).
 */
interface AIConstructionRepositoryInterface
{
    /**
     * Абсолютно надежно сохраняет свежесгенерированный проект или расчет от нейронной сети.
     *
     * @param AIConstruction $construction Полноценная доменная сущность конструкции.
     * @return void
     */
    public function save(AIConstruction $construction): void;

    /**
     * Безупречно извлекает полную историю AI-генераций для конкретного профиля пользователя с учетом тенанта.
     *
     * @param int $userId Идентификатор владельца генераций.
     * @param int $tenantId Идентификатор тенанта (строгая защита данных).
     * @param string|null $vertical Опциональная фильтрация генераций по вертикали.
     * @param int $limit Жестко ограниченный лимит выдачи.
     * @return Collection|AIConstruction[] Коллекция доменных объектов AI-дизайнов.
     */
    public function getByUserIdAndTenant(int $userId, int $tenantId, ?string $vertical = null, int $limit = 10): Collection;
}
