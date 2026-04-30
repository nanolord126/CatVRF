<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Exception;

final class ShelfLifeException extends Exception
{
    public static function expiredItem(string $itemName, string $expiryDate): self
    {
        return new self(
            "Товар просрочен: {$itemName} (СОГ до {$expiryDate})"
        );
    }

    public static function expiryDateRequired(string $itemName): self
    {
        return new self(
            "Обязательно указание срока годности для товара: {$itemName}"
        );
    }

    public static function batchNumberRequired(string $itemName): self
    {
        return new self(
            "Обязательно указание номера партии для товара: {$itemName}"
        );
    }
}
