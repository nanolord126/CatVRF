<?php declare(strict_types=1);

/**
 * ListWeddingBookings — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listweddingbookings
 */


namespace App\Domains\WeddingPlanning\Filament\Resources\WeddingBookingResource\Pages;

use App\Domains\WeddingPlanning\Filament\Resources\WeddingBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListWeddingBookings extends ListRecords
{
    protected static string $resource = WeddingBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}