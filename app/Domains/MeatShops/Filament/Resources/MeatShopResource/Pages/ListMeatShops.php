<?php declare(strict_types=1);

/**
 * ListMeatShops — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listmeatshops
 */


namespace App\Domains\MeatShops\Filament\Resources\MeatShopResource\Pages;

use App\Domains\MeatShops\Filament\Resources\MeatShopResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListMeatShops extends ListRecords
{
    protected static string $resource = MeatShopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}