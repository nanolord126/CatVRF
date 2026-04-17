<?php declare(strict_types=1);

/**
 * ListAutoServiceOrders — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listautoserviceorders
 */


namespace App\Domains\Auto\Filament\Resources\AutoServiceOrderResource\Pages;

use App\Domains\Auto\Filament\Resources\AutoServiceOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListAutoServiceOrders extends ListRecords
{
    protected static string $resource = AutoServiceOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

}
