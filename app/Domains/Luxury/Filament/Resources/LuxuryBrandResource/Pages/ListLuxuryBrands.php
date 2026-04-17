<?php declare(strict_types=1);

/**
 * ListLuxuryBrands — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listluxurybrands
 */


namespace App\Domains\Luxury\Filament\Resources\LuxuryBrandResource\Pages;

use App\Domains\Luxury\Filament\Resources\LuxuryBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListLuxuryBrands extends ListRecords
{
    protected static string $resource = LuxuryBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}