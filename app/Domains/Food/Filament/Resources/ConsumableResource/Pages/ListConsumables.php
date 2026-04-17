<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources\ConsumableResource\Pages;

use App\Domains\Food\Filament\Resources\ConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListConsumables extends ListRecords
{
    protected static string $resource = ConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
