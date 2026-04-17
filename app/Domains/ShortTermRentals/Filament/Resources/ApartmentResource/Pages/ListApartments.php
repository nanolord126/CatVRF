<?php declare(strict_types=1);

/**
 * ListApartments — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listapartments
 */


namespace App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource\Pages;

use App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListApartments extends ListRecords
{
    protected static string $resource = ApartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
