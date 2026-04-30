<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Exceptions;

use RuntimeException;

/**
 * Encryption Exception
 */
final class EncryptionException extends RuntimeException
{
    public static function encryptionFailed(string $message): self
    {
        return new self("Encryption failed: {$message}");
    }

    public static function decryptionFailed(string $message): self
    {
        return new self("Decryption failed: {$message}");
    }

    public static function invalidKey(string $message): self
    {
        return new self("Invalid encryption key: {$message}");
    }
}
