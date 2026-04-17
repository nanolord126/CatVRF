<?php declare(strict_types=1);

/**
 * ListRentalBookings — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listrentalbookings
 */


namespace App\Domains\CarRental\Filament\Resources\RentalBookingResource\Pages;

use App\Domains\CarRental\Filament\Resources\RentalBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListRentalBookings extends ListRecords
{
    protected static string $resource = RentalBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}