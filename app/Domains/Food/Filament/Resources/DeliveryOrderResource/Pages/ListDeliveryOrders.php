<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources\DeliveryOrderResource\Pages;

use App\Domains\Food\Filament\Resources\DeliveryOrderResource;
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
