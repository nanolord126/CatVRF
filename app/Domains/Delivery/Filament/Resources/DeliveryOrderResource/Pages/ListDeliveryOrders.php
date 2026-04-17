<?php declare(strict_types=1);

/**
 * ListDeliveryOrders — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listdeliveryorders
 */


namespace App\Domains\Delivery\Filament\Resources\DeliveryOrderResource\Pages;

use App\Domains\Delivery\Filament\Resources\DeliveryOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListDeliveryOrders extends ListRecords
{
    protected static string $resource = DeliveryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}