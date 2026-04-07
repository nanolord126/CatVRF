<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Абсолютно иммутабельный объект значения (Value Object), представляющий промокод.
 *
 * Категорически инкапсулирует строку промокода, гарантируя ее синтаксическую корректность,
 * приведение к верхнему регистру и строгое соответствие инфраструктурным ограничениям.
 */
final readonly class PromoCode
{
    /**
     * Инициализирует и строго валидирует объект промокода.
     *
     * @param string $value Исходное строковое значение промокода, подлежащее нормализации и проверке.
     * @throws InvalidArgumentException Если промокод пуст или превышает допустимую длину.
     */
    public function __construct(
        public string $value
    ) {
        $normalized = trim(strtoupper($value));

        if ($normalized === '') {
            throw new InvalidArgumentException('Категорически запрещено использовать пустой промокод.');
        }

        if (mb_strlen($normalized) > 50) {
            throw new InvalidArgumentException('Промокод гарантированно не может превышать 50 символов.');
        }

        if (!preg_match('/^[A-Z0-9_\-]+$/', $normalized)) {
            throw new InvalidArgumentException('Промокод должен строго состоять из букв, цифр, подчеркиваний и дефисов.');
        }

        $this->value = $normalized;
    }

    /**
     * Безупречно возвращает строковое представление промокода.
     *
     * @return string Категорически нормализованный промокод.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Строго сравнивает текущий промокод с другим объектом PromoCode на предмет абсолютного совпадения.
     *
     * @param PromoCode $other Другой промокод для сравнения.
     * @return bool Истинно, если коды абсолютно идентичны.
     */
    public function equals(PromoCode $other): bool
    {
        return $this->value === $other->value;
    }
}
