<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\ValueObjects;

/**
 * ContactInfo — Value Object контактных данных сотрудника.
 *
 * Хранит email и телефон сотрудника. Самовалидируется при создании.
 * Сравнение — по нормализованному email (нижний регистр).
 */
final readonly class ContactInfo
{
    /** Максимальная длина телефонного номера в цифрах (без +7 и пробелов). */
    private const MAX_PHONE_DIGITS = 11;

    /** Минимальная длина цифр телефонного номера. */
    private const MIN_PHONE_DIGITS = 7;

    public function __construct(
        private readonly string $email,
        private ?string $phone = null) {
        $this->validateEmail($email);

        if ($phone !== null) {
            $this->validatePhone($phone);
        }
    }

    /**
     * Создаёт ContactInfo из массива.
     *
     * @param array{email: string, phone?: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            phone: $data['phone'] ?? null,
        );
    }

    /**
     * Проверяет, заполнен ли телефонный номер.
     */
    public function hasPhone(): bool
    {
        return $this->phone !== null;
    }

    /**
     * Сравнивает два ContactInfo по нормализованному email.
     */
    public function equals(self $other): bool
    {
        return mb_strtolower($this->email) === mb_strtolower($other->email);
    }

    /**
     * Ретурнирует email в нижнем регистре.
     */
    public function normalizedEmail(): string
    {
        return mb_strtolower($this->email);
    }

    /**
     * Конвертирует в массив для передачи в API-ответ.
     *
     * @return array{email: string, phone: string|null}
     */
    public function toArray(): array
    {
        return [
            'email' => $this->normalizedEmail(),
            'phone' => $this->phone,
        ];
    }

    /**
     * Валидирует email-адрес. Бросает \InvalidArgumentException при некорректном.
     *
     * @throws \InvalidArgumentException
     */
    private function validateEmail(string $email): void
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('Некорректный формат email: %s', $email)
            );
        }
    }

    /**
     * Валидирует телефонный номер. Бросает \InvalidArgumentException при некорректном.
     *
     * @throws \InvalidArgumentException
     */
    private function validatePhone(string $phone): void
    {
        $digits = preg_replace('/\D/', '', $phone);
        $digitCount = strlen($digits ?? '');

        if ($digitCount < self::MIN_PHONE_DIGITS || $digitCount > self::MAX_PHONE_DIGITS) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Некорректный телефонный номер: %s (ожидается %d–%d цифр)',
                    $phone,
                    self::MIN_PHONE_DIGITS,
                    self::MAX_PHONE_DIGITS,
                )
            );
        }
    }
}
