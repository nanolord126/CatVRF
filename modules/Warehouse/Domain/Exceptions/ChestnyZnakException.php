<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Exceptions;

use RuntimeException;

/**
 * Chestny ZNAK Exception
 */
final class ChestnyZnakException extends RuntimeException
{
    public static function checkFailed(string $code, string $error): self
    {
        return new self("Marking code check failed for {$code}: {$error}");
    }

    public static function checkBatchFailed(string $error): self
    {
        return new self("Batch marking code check failed: {$error}");
    }

    public static function registrationFailed(string $documentNumber, string $error): self
    {
        return new self("Document registration failed for {$documentNumber}: {$error}");
    }

    public static function statusCheckFailed(string $documentId, string $error): self
    {
        return new self("Status check failed for document {$documentId}: {$error}");
    }

    public static function infoCheckFailed(string $code, string $error): self
    {
        return new self("Code info check failed for {$code}: {$error}");
    }

    public static function signingFailed(string $documentId, string $error): self
    {
        return new self("Document signing failed for {$documentId}: {$error}");
    }

    public static function sendFailed(string $documentId, string $error): self
    {
        return new self("Document send failed for {$documentId}: {$error}");
    }

    public static function documentsListFailed(string $error): self
    {
        return new self("Documents list retrieval failed: {$error}");
    }

    public static function invalidWebhookPayload(): self
    {
        return new self("Invalid webhook payload");
    }

    public static function connectionError(string $error): self
    {
        return new self("Chestny ZNAK connection error: {$error}");
    }

    public static function invalidResponse(): self
    {
        return new self("Invalid response from Chestny ZNAK API");
    }
}
