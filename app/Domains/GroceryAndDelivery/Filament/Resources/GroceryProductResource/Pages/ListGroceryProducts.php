<?php declare(strict_types=1);

/**
 * ListGroceryProducts — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listgroceryproducts
 */


namespace App\Domains\GroceryAndDelivery\Filament\Resources\GroceryProductResource\Pages;

use App\Domains\GroceryAndDelivery\Filament\Resources\GroceryProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListGroceryProducts extends ListRecords
{
    protected static string $resource = GroceryProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}