<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Inventory\Domain\Entities\InventoryItem;
use Modules\Inventory\Domain\Exceptions\ShelfLifeException;

final class InventoryItemPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can use an inventory item
     */
    public function useItem(?object $user, InventoryItem $item): bool
    {
        // Hard block on expired items
        if ($item->isExpired()) {
            throw ShelfLifeException::expiredItem(
                $item->name,
                $item->expiryDate?->format('Y-m-d') ?? 'N/A'
            );
        }

        // Block items in quarantine
        if (!$item->isUsable()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can sell an inventory item
     */
    public function sellItem(?object $user, InventoryItem $item, int $quantity): bool
    {
        if (!$this->useItem($user, $item)) {
            return false;
        }

        // Check if sufficient quantity
        if ($item->quantity < $quantity) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can create inventory items
     */
    public function create(?object $user): bool
    {
        return true; // Adjust based on your auth requirements
    }

    /**
     * Determine if the user can update inventory items
     */
    public function update(?object $user, InventoryItem $item): bool
    {
        return true; // Adjust based on your auth requirements
    }

    /**
     * Determine if the user can delete inventory items
     */
    public function delete(?object $user, InventoryItem $item): bool
    {
        return true; // Adjust based on your auth requirements
    }
}
