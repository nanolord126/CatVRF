<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerConsumableResource\Pages;

use App\Filament\Tenant\Resources\Flowers\FlowerConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListFlowerConsumables extends ListRecords
{
    protected static string $resource = FlowerConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }
}
