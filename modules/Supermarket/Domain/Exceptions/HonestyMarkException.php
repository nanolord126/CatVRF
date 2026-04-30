<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Exceptions;

use Exception;

final class HonestyMarkException extends Exception
{
    public static function validationFailed(string $error): self
    {
        return new self("Honesty Mark validation failed: {$error}", 422);
    }

    public static function withdrawalFailed(string $error): self
    {
        return new self("Honesty Mark withdrawal failed: {$error}", 500);
    }

    public static function serviceUnavailable(): self
    {
        return new self('Honesty Mark service is currently unavailable', 503);
    }
}
