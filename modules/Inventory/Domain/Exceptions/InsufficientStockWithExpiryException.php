<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Exception;

final class InsufficientStockWithExpiryException extends Exception
{
    public static function create(int $remainingQuantity): self
    {
        return new self(
            "Недостаточно товара с действующим СОГ. Не хватает: {$remainingQuantity} ед."
        );
    }

    public static function forItem(string $itemName, int $requested, int $available): self
    {
        return new self(
            "Недостаточно товара '{$itemName}' с действующим СОГ. Запрошено: {$requested}, доступно: {$available}"
        );
    }
}
