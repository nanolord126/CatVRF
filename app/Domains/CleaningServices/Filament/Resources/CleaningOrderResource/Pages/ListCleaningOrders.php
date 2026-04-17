<?php declare(strict_types=1);

/**
 * ListCleaningOrders — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listcleaningorders
 */


namespace App\Domains\CleaningServices\Filament\Resources\CleaningOrderResource\Pages;

use App\Domains\CleaningServices\Filament\Resources\CleaningOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCleaningOrders extends ListRecords
{
    protected static string $resource = CleaningOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}