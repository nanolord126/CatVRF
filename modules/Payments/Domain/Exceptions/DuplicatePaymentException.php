<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\Exceptions;

final class DuplicatePaymentException extends PaymentDomainException
{
    public static function forPayment(string $paymentId): self
    {
        return new self("Payment with ID {$paymentId} is a duplicate.");
    }
}
