<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\Exceptions;

final class PaymentNotFoundException extends \DomainException
{
    public static function forId(string $id): self
    {
        return new self("Payment not found: {$id}");
    }
}
