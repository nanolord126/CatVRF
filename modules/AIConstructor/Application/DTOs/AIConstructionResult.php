<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Application\DTOs;

/**
 * Исключительно иммутабельный транспортный класс (DTO), строго представляющий
 * универсальный и консистентный ответ фреймворка AI-генерации под любую из 52 вертикалей.
 *
 * Безупречно передает данные от слоя Application к слою Presentation без утечек сущностей домена.
 */
final readonly class AIConstructionResult
{
    /**
     * Категорически инициализирует структуру результата AI-моделирования.
     *
     * @param string $vertical Строгое текстовое наименование вертикали.
     * @param string $type Строковое представление формата отдачи (image, list, design, calculation).
     * @param array<string, mixed> $payload Основная сгенерированная полезная нагрузка (изображение, JSON параметры).
     * @param array<int> $suggestions Массив рекомендованных товаров/услуг, строго подтвержденных наличием в Inventory.
     * @param float $confidence_score Уровень уверенности модели от 0.0 до 1.0 (для логирования или бизнес-блокировок).
     * @param string $correlation_id Обязательный UUID трассировки для сквозного аудита системы.
     */
    public function __construct(
        public string $vertical,
        public string $type,
        public array $payload,
        public array $suggestions,
        public float $confidence_score,
        public string $correlation_id
    ) {
    }
}
