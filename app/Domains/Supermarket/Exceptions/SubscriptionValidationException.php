<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Exceptions;

use Exception;

final class SubscriptionValidationException extends Exception
{
    public function __construct(string $message, public readonly array $errors = [])
    {
        parent::__construct($message);
    }

    public static function minimumAmount(float $amount, float $minimum): self
    {
        return new self(
            "Minimum subscription amount is {$minimum} ₽, current: {$amount} ₽",
            ['amount' => $amount, 'minimum' => $minimum]
        );
    }

    public static function minimumQuantity(int $quantity, int $minimum): self
    {
        return new self(
            "Minimum {$minimum} items required, current: {$quantity}",
            ['quantity' => $quantity, 'minimum' => $minimum]
        );
    }

    public static function ageVerificationRequired(): self
    {
        return new self(
            'Age verification required for 18+ products',
            ['reason' => 'age_restricted_products']
        );
    }

    public static function sellerNotActive(): self
    {
        return new self(
            'Seller is not active',
            ['reason' => 'seller_inactive']
        );
    }
}
