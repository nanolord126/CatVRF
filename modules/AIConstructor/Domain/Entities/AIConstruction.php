<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Domain\Entities;

use Modules\AIConstructor\Domain\Enums\AIConstructionType;
use Modules\AIConstructor\Domain\ValueObjects\ConfidenceScore;
use Carbon\CarbonImmutable;

/**
 * Ключевая доменная сущность (Aggregate Root), строго представляющая результат
 * работы унифицированного фреймворка AI-генерации (User AI Design / Construction).
 *
 * Безупречно объединяет данные о пользователе, вертикали, типе генерации и спецификации товаров.
 * Категорически гарантирует консистентность состояния и изоляцию мультитенантности.
 */
final class AIConstruction
{
    /**
     * Строго инициирует абсолютно консистентную доменную сущность AI-конструкции.
     *
     * @param string $id Уникальный UUID генерации.
     * @param int $tenantId Идентификатор тенанта (строгая B2B/B2C изоляция).
     * @param int $userId Идентификатор зарегистрированного пользователя-инициатора.
     * @param string $vertical Строгий текстовый код вертикали (beauty, furniture, food и т.д.).
     * @param AIConstructionType $type Категорически типизированный формат выдачи нейросети.
     * @param array<string, mixed> $designData Полиморфный JSON-payload генерации (фото, координаты, текст).
     * @param array<int> $suggestionItemIds Массив идентификаторов товаров к резервированию из Inventory.
     * @param ConfidenceScore $confidenceScore Уровень математической уверенности алгоритма в выдаче.
     * @param string $correlationId Единый трассировочный ID системы для логов аудита.
     * @param CarbonImmutable|null $createdAt Дата фактической инициации генерации.
     */
    public function __construct(
        private readonly string $id,
        private readonly int $tenantId,
        private readonly int $userId,
        private readonly string $vertical,
        private readonly AIConstructionType $type,
        private array $designData,
        private array $suggestionItemIds,
        private readonly ConfidenceScore $confidenceScore,
        private readonly string $correlationId,
        private readonly ?CarbonImmutable $createdAt = null
    ) {
    }

    /**
     * @return string Возвращает уникальный идентификатор генерации.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return int Возвращает ID пользователя.
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return int Возвращает ID тенанта.
     */
    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    /**
     * @return string Возвращает код вертикали.
     */
    public function getVertical(): string
    {
        return $this->vertical;
    }

    /**
     * @return AIConstructionType Возвращает строго типизированный формат результата.
     */
    public function getType(): AIConstructionType
    {
        return $this->type;
    }

    /**
     * @return array Возвращает основную полезную нагрузку AI.
     */
    public function getDesignData(): array
    {
        return $this->designData;
    }

    /**
     * @return array Возвращает список рекомендованных Item ID.
     */
    public function getSuggestionItemIds(): array
    {
        return $this->suggestionItemIds;
    }

    /**
     * @return float Возвращает нормализованный коэффициент достоверности алгоритма.
     */
    public function getConfidenceValue(): float
    {
        return $this->confidenceScore->getValue();
    }

    /**
     * @return string Возвращает идентификатор связи логов.
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}
