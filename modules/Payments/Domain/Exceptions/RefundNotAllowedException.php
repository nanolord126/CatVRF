<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\Exceptions;

use Modules\Payments\Domain\ValueObjects\PaymentStatus;

final class RefundNotAllowedException extends \DomainException
{
    public static function forStatus(PaymentStatus $status): self
    {
        return new self(
            "Refund is not allowed for payment in status '{$status->value}'. " .
            'Only CAPTURED payments can be refunded.'
        );
    }
}
