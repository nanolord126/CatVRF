<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Exceptions;

use Exception;

final class AgeRestrictionException extends Exception
{
    public function __construct(
        string $message = 'Для товаров 18+ требуется подтверждение возраста',
        public readonly array $meta = [],
        public readonly ?string $verificationUrl = null
    ) {
        parent::__construct($message, 403);
    }

    public static function forProduct(string $productName): self
    {
        return new self(
            message: "Товар '{$productName}' доступен только для пользователей 18+",
            meta: ['product_name' => $productName],
        );
    }

    public static function forOrder(int $restrictedCount): self
    {
        return new self(
            message: "Заказ содержит {$restrictedCount} товаров для взрослых. Требуется верификация возраста.",
            meta: ['restricted_items_count' => $restrictedCount],
        );
    }

    public static function verificationRequired(array $methods): self
    {
        return new self(
            message: 'Требуется подтверждение возраста через один из доступных методов',
            meta: ['available_methods' => $methods],
            verificationUrl: '/verify-age',
        );
    }

    public static function verificationFailed(string $reason): self
    {
        return new self(
            message: "Верификация возраста не пройдена: {$reason}",
            meta: ['failure_reason' => $reason],
        );
    }

    public static function verificationExpired(): self
    {
        return new self(
            message: 'Срок действия верификации истёк. Пожалуйста, подтвердите возраст снова.',
            meta: ['expired' => true],
        );
    }

    public function getRestrictedItemsCount(): int
    {
        return $this->meta['restricted_items_count'] ?? 0;
    }

    public function getProductName(): ?string
    {
        return $this->meta['product_name'] ?? null;
    }

    public function getAvailableMethods(): array
    {
        return $this->meta['available_methods'] ?? [];
    }

    public function getFailureReason(): ?string
    {
        return $this->meta['failure_reason'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'code' => $this->code,
            'meta' => $this->meta,
            'verification_url' => $this->verificationUrl,
        ];
    }
}
