<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Payment Method Value Object - immutable representation of payment method.
 */
final readonly class PaymentMethod
{
    public const string CARD = 'card';
    public const string SBP = 'sbp';
    public const string SBER_PAY = 'sber_pay';
    public const string YOO_MONEY = 'yoo_money';
    public const string APPLE_PAY = 'apple_pay';
    public const string GOOGLE_PAY = 'google_pay';
    public const string BANK_TRANSFER = 'bank_transfer';
    public const string CASH = 'cash';
    public const string INSTALLMENTS = 'installments';
    public const string CREDIT = 'credit';

    private const array VALID_METHODS = [
        self::CARD,
        self::SBP,
        self::SBER_PAY,
        self::YOO_MONEY,
        self::APPLE_PAY,
        self::GOOGLE_PAY,
        self::BANK_TRANSFER,
        self::CASH,
        self::INSTALLMENTS,
        self::CREDIT,
    ];

    public function __construct(
        public string $value,
        public ?string $provider = null,
        public ?string $cardLast4 = null,
        public ?string $cardBrand = null,
    ) {
        if (!in_array($value, self::VALID_METHODS, true)) {
            throw new InvalidArgumentException("Invalid payment method: {$value}");
        }
    }

    public static function fromString(string $method): self
    {
        return new self($method);
    }

    public static function card(string $last4, string $brand): self
    {
        return new self(self::CARD, null, $last4, $brand);
    }

    public static function sbp(): self
    {
        return new self(self::SBP);
    }

    public static function sberPay(): self
    {
        return new self(self::SBER_PAY);
    }

    public static function bankTransfer(): self
    {
        return new self(self::BANK_TRANSFER);
    }

    public function isCard(): bool
    {
        return $this->value === self::CARD;
    }

    public function isDigitalWallet(): bool
    {
        return in_array($this->value, [self::SBER_PAY, self::YOO_MONEY, self::APPLE_PAY, self::GOOGLE_PAY], true);
    }

    public function isInstallment(): bool
    {
        return in_array($this->value, [self::INSTALLMENTS, self::CREDIT], true);
    }

    public function isOffline(): bool
    {
        return $this->value === self::CASH;
    }
}
