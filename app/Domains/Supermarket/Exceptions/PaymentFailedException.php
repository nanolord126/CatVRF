<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Exceptions;

use Exception;

final class PaymentFailedException extends Exception
{
    public function __construct(string $message, public readonly ?string $gatewayErrorCode = null)
    {
        parent::__construct($message);
    }

    public static function fromGateway(string $message, ?string $errorCode = null): self
    {
        return new self($message, $errorCode);
    }

    public static function insufficientFunds(): self
    {
        return new self('Insufficient funds', 'insufficient_funds');
    }

    public static function cardDeclined(): self
    {
        return new self('Card declined', 'card_declined');
    }

    public static function timeout(): self
    {
        return new self('Payment gateway timeout', 'timeout');
    }
}
