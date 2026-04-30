<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Exceptions;

use Exception;

final class HonestyMarkException extends Exception
{
    public static function invalidMark(string $reason): self
    {
        return new self("Invalid Honest Mark: {$reason}");
    }

    public static function markNotFound(string $dataMatrix): self
    {
        return new self("Mark not found: {$dataMatrix}");
    }

    public static function withdrawalFailed(string $reason): self
    {
        return new self("Mark withdrawal failed: {$reason}");
    }

    public static function certificateInvalid(string $reason): self
    {
        return new self("Certificate invalid: {$reason}");
    }

    public static function serviceUnavailable(): self
    {
        return new self('Honest Mark service is temporarily unavailable');
    }
}
