<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Exceptions;

use Exception;

/**
 * ЕГИСЗ Exception
 * 
 * Исключение для ошибок интеграции с ЕГИСЗ
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class EgiszException extends Exception
{
    public static function integrationFailed(string $message): self
    {
        return new self("ЕГИСЗ integration failed: {$message}");
    }

    public static function licenseVerificationFailed(string $licenseNumber): self
    {
        return new self("License verification failed for: {$licenseNumber}");
    }

    public static function organizationRegistrationFailed(string $ogrn): self
    {
        return new self("Organization registration failed for OGRN: {$ogrn}");
    }

    public static function dataSubmissionFailed(string $type): self
    {
        return new self("Data submission failed for type: {$type}");
    }

    public static function invalidConfiguration(string $configKey): self
    {
        return new self("Invalid ЕГИСЗ configuration: {$configKey}");
    }
}
