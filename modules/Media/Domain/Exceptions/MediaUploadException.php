<?php

declare(strict_types=1);

namespace Modules\Media\Domain\Exceptions;

use Exception;

final class MediaUploadException extends Exception
{
    public static function invalidMimeType(string $mimeType): self
    {
        return new self("Invalid MIME type: {$mimeType}");
    }

    public static function fileTooLarge(int $maxSizeBytes): self
    {
        $maxSizeMb = round($maxSizeBytes / 1024 / 1024, 2);
        return new self("File size exceeds maximum allowed size of {$maxSizeMb}MB");
    }

    public static function invalidDimensions(int $minWidth, int $minHeight): self
    {
        return new self("Image dimensions must be at least {$minWidth}x{$minHeight}");
    }

    public static function tenantIsolationViolation(): self
    {
        return new self("Tenant isolation violation detected");
    }

    public static function storageFailure(string $reason): self
    {
        return new self("Storage failure: {$reason}");
    }
}
