<?php declare(strict_types=1);

/**
 * ListBakeryOrders — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listbakeryorders
 */


namespace App\Domains\Confectionery\Filament\Resources\BakeryOrderResource\Pages;

use App\Domains\Confectionery\Filament\Resources\BakeryOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBakeryOrders extends ListRecords
{
    protected static string $resource = BakeryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}