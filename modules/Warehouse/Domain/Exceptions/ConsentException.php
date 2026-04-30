<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Exceptions;

use RuntimeException;

/**
 * Consent Exception
 */
final class ConsentException extends RuntimeException
{
    public static function notFoundOrAlreadyRevoked(string $consentId): self
    {
        return new self("Consent not found or already revoked: {$consentId}");
    }

    public static function invalidConsentType(string $consentType, array $availableTypes): self
    {
        return new self("Invalid consent type: {$consentType}. Available types: " . implode(', ', $availableTypes));
    }

    public static function requiredConsentMissing(string $consentType): self
    {
        return new self("Required consent missing: {$consentType}");
    }
}
