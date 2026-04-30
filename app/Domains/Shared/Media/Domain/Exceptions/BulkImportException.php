<?php

declare(strict_types=1);

namespace Modules\Media\Domain\Exceptions;

use Exception;

final class BulkImportException extends Exception
{
    public static function invalidFormat(string $expected, string $actual): self
    {
        return new self("Invalid format: expected {$expected}, got {$actual}");
    }

    public static function missingRequiredField(string $field): self
    {
        return new self("Missing required field: {$field}");
    }

    public static function zipExtractionFailed(): self
    {
        return new self("Failed to extract ZIP file");
    }

    public static function rowValidationError(int $rowNumber, array $errors): self
    {
        $errorString = implode(', ', $errors);
        return new self("Row {$rowNumber} validation error: {$errorString}");
    }
}
