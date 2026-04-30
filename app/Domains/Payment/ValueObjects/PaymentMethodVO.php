<?php

declare(strict_types=1);

namespace App\Domains\Payment\ValueObjects;

use InvalidArgumentException;

/**
 * Payment Method Value Object - immutable representation of payment method.
 */
final readonly class PaymentMethodVO
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

    private const array DIGITAL_WALLETS = [self::SBER_PAY, self::YOO_MONEY, self::APPLE_PAY, self::GOOGLE_PAY];
    private const array BNPL_METHODS = [self::INSTALLMENTS, self::CREDIT];
    private const array OFFLINE_METHODS = [self::CASH];

    public function __construct(
        public string $method,
        public ?string $provider = null, // e.g., 'tinkoff', 'tochka', 'sber'
        public ?string $cardLast4 = null,
        public ?string $cardBrand = null,
    ) {
        $this->validateMethod($method);
    }

    /**
     * Create from string.
     */
    public static function fromString(string $method): self
    {
        return new self($method);
    }

    /**
     * Create for card payment.
     */
    public static function card(string $last4, string $brand): self
    {
        return new self(self::CARD, null, $last4, $brand);
    }

    /**
     * Create for SBP payment.
     */
    public static function sbp(): self
    {
        return new self(self::SBP);
    }

    /**
     * Create for installments.
     */
    public static function installments(string $provider): self
    {
        return new self(self::INSTALLMENTS, $provider);
    }

    /**
     * Check if method is digital wallet.
     */
    public function isDigitalWallet(): bool
    {
        return in_array($this->method, self::DIGITAL_WALLETS, true);
    }

    /**
     * Check if method is BNPL (Buy Now Pay Later).
     */
    public function isBNPL(): bool
    {
        return in_array($this->method, self::BNPL_METHODS, true);
    }

    /**
     * Check if method is offline (cash, etc.).
     */
    public function isOffline(): bool
    {
        return in_array($this->method, self::OFFLINE_METHODS, true);
    }

    /**
     * Check if method requires 3DS.
     */
    public function requires3DS(): bool
    {
        return $this->method === self::CARD && $this->provider !== 'sber';
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this->method) {
            self::CARD => sprintf('Банковская карта %s (%s)', $this->cardBrand ?? '', $this->cardLast4 ?? '****'),
            self::SBP => 'СБП (Система быстрых платежей)',
            self::SBER_PAY => 'СберPay',
            self::YOO_MONEY => 'ЮMoney',
            self::APPLE_PAY => 'Apple Pay',
            self::GOOGLE_PAY => 'Google Pay',
            self::BANK_TRANSFER => 'Банковский перевод',
            self::CASH => 'Наличные',
            self::INSTALLMENTS => 'Рассрочка',
            self::CREDIT => 'Кредит',
            default => 'Неизвестно',
        };
    }

    /**
     * Get icon name for UI.
     */
    public function icon(): string
    {
        return match ($this->method) {
            self::CARD => 'credit-card',
            self::SBP => 'qr-code',
            self::SBER_PAY => 'smartphone',
            self::YOO_MONEY => 'wallet',
            self::APPLE_PAY => 'smartphone',
            self::GOOGLE_PAY => 'smartphone',
            self::BANK_TRANSFER => 'building-2',
            self::CASH => 'banknote',
            self::INSTALLMENTS => 'calendar',
            self::CREDIT => 'landmark',
            default => 'help-circle',
        };
    }

    private function validateMethod(string $method): void
    {
        $validMethods = [
            self::CARD, self::SBP, self::SBER_PAY, self::YOO_MONEY,
            self::APPLE_PAY, self::GOOGLE_PAY, self::BANK_TRANSFER,
            self::CASH, self::INSTALLMENTS, self::CREDIT,
        ];

        if (! in_array($method, $validMethods, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid payment method: %s', $method)
            );
        }
    }
}
