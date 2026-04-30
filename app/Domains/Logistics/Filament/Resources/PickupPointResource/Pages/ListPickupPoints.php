<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources\PickupPointResource\Pages;

use App\Domains\Logistics\Filament\Resources\PickupPointResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListPickupPoints extends ListRecords
{
    protected static string $resource = PickupPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
