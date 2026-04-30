<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Exceptions;

use Exception;

/**
 * Domain Exceptions for Warehouse
 *
 * All exceptions combined to avoid stub files
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */

final class WarehouseException extends Exception
{
}

final class InsufficientStockException extends WarehouseException
{
    public function __construct(int $requested, int $available)
    {
        parent::__construct("Insufficient stock: requested {$requested}, available {$available}");
    }
}

final class ProductNotFoundException extends WarehouseException
{
    public function __construct(string $productSku)
    {
        parent::__construct("Product not found: {$productSku}");
    }
}

final class BatchNotFoundException extends WarehouseException
{
    public function __construct(string $batchNumber)
    {
        parent::__construct("Batch not found: {$batchNumber}");
    }
}

final class InvalidLocationException extends WarehouseException
{
    public function __construct(string $location)
    {
        parent::__construct("Invalid location: {$location}");
    }
}

final class InventoryCountException extends WarehouseException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

final class LicenseManagementException extends WarehouseException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

final class ControlledSubstancesException extends WarehouseException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

final class PIIProtectionException extends WarehouseException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

final class ChestnyZnakException extends WarehouseException
{
    public static function checkFailed(string $code, string $details): self
    {
        return new self("Chestny ZNAK check failed for code {$code}: {$details}");
    }

    public static function registrationFailed(string $documentNumber, string $details): self
    {
        return new self("Chestny ZNAK registration failed for document {$documentNumber}: {$details}");
    }

    public static function statusCheckFailed(string $documentId, string $details): self
    {
        return new self("Chestny ZNAK status check failed for document {$documentId}: {$details}");
    }

    public static function connectionError(string $message): self
    {
        return new self("Chestny ZNAK connection error: {$message}");
    }

    public static function invalidResponse(): self
    {
        return new self("Chestny ZNAK invalid response received");
    }
}
