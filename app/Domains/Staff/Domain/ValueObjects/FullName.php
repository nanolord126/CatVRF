<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\ValueObjects;

/**
 * FullName — Value Object полного имени сотрудника.
 *
 * Обеспечивает форматирование имени (полное, сокращённое, инициалы)
 * для B2C-профиля и B2B-админки.
 */
final readonly class FullName
{
    public function __construct(
        private readonly string $firstName,
        private readonly string $lastName,
        private ?string $middleName = null) {
        if (empty(trim($firstName)) || empty(trim($lastName))) {
            throw new \InvalidArgumentException('Имя и фамилия не могут быть пустыми.');
        }
    }

    /**
     * Создаёт FullName из массива.
     *
     * @param array{first_name: string, last_name: string, middle_name?: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName:  $data['first_name'],
            lastName:   $data['last_name'],
            middleName: $data['middle_name'] ?? null,
        );
    }

    /**
     * Возвращает полное имя: "Фамилия Имя Отчество".
     */
    public function full(): string
    {
        return trim($this->lastName . ' ' . $this->firstName . ' ' . ($this->middleName ?? ''));
    }

    /**
     * Возвращает публичное имя: "Фамилия И." или "Фамилия И.О."
     */
    public function short(): string
    {
        $initials = mb_substr($this->firstName, 0, 1) . '.';

        if ($this->middleName !== null) {
            $initials .= mb_substr($this->middleName, 0, 1) . '.';
        }

        return $this->lastName . ' ' . $initials;
    }

    /**
     * Возвращает инициалы для аватара: "ИО" или "И".
     */
    public function initials(): string
    {
        $result = mb_strtoupper(mb_substr($this->firstName, 0, 1));

        if ($this->middleName !== null) {
            $result .= mb_strtoupper(mb_substr($this->middleName, 0, 1));
        }

        return $result;
    }

    /**
     * Сравнивает два FullName.
     */
    public function equals(self $other): bool
    {
        return $this->firstName === $other->firstName
            && $this->lastName === $other->lastName
            && $this->middleName === $other->middleName;
    }

    /**
     * Конвертирует в массив для API-ответа.
     *
     * @return array{first_name: string, last_name: string, middle_name: string|null, full: string, short: string}
     */
    public function toArray(): array
    {
        return [
            'first_name'  => $this->firstName,
            'last_name'   => $this->lastName,
            'middle_name' => $this->middleName,
            'full'        => $this->full(),
            'short'       => $this->short(),
        ];
    }
}
