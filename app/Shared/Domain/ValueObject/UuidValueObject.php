<?php declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Базовый Value Object для UUID-идентификаторов.
 *
 * Канон CatVRF 2026: declare(strict_types=1), readonly class.
 * Используется как родитель для RideId, DriverId и аналогичных VO.
 *
 * @package App\Shared\Domain\ValueObject
 */
class UuidValueObject
{
    /**
     * @param string $id UUID-строка
     */
    public function __construct(
        protected string $id,
    ) {
        if (empty($this->id)) {
            throw new InvalidArgumentException(
                static::class . ': UUID не может быть пустым.'
            );
        }
    }

    /**
     * Создать экземпляр из строки.
     */
    public static function fromString(string $id): static
    {
        return new static($id);
    }

    /**
     * Получить строковое представление UUID.
     */
    public function toString(): string
    {
        return $this->id;
    }

    /**
     * Магический метод для приведения к строке.
     */
    public function __toString(): string
    {
        return $this->id;
    }

    /**
     * Сравнить с другим Value Object.
     */
    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }
}
