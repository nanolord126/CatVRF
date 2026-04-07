<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerOrderResource\Pages;

use App\Filament\Tenant\Resources\Flowers\FlowerOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListFlowerOrders extends ListRecords
{
    protected static string $resource = FlowerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
