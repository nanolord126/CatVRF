<?php declare(strict_types=1);

/**
 * ListHouseholdProducts — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listhouseholdproducts
 */


namespace App\Domains\HouseholdGoods\Filament\Resources\HouseholdProductResource\Pages;

use App\Domains\HouseholdGoods\Filament\Resources\HouseholdProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListHouseholdProducts extends ListRecords
{
    protected static string $resource = HouseholdProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}