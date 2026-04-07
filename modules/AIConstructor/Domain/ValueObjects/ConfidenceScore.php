<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Абсолютно строгий и иммутабельный объект значения (Value Object), представляющий
 * коэффициент уверенности нейросети в сгенерированном ею ответе (Confidence Score).
 *
 * Категорически инкапсулирует значение с плавающей точкой в строгом диапазоне от 0.0 до 1.0,
 * безупречно защищая слой бизнес-логики от внедрения некорректных математических вероятностей.
 */
final readonly class ConfidenceScore
{
    /**
     * Инициализирует и категорически валидирует коэффициент уверенности AI-модели.
     *
     * @param float $score Значение уверенности генерации.
     * @throws InvalidArgumentException Если значение выходит за пределы [0.0, 1.0].
     */
    public function __construct(
        public float $score
    ) {
        if ($score < 0.0 || $score > 1.0) {
            throw new InvalidArgumentException('Коэффициент уверенности AI должен категорически находиться в диапазоне от 0.0 до 1.0.');
        }
    }

    /**
     * Безупречно возвращает валидированное значение уверенности генерации.
     *
     * @return float Нормализованное значение между 0.0 и 1.0.
     */
    public function getValue(): float
    {
        return $this->score;
    }

    /**
     * Строго проверяет, является ли результат нейронной генерации абсолютно высококачественным
     * и применимым без участия оператора.
     *
     * @return bool Истинно, если уверенность превышает порог 0.85.
     */
    public function isHighConfidence(): bool
    {
        return $this->score >= 0.85;
    }
}
