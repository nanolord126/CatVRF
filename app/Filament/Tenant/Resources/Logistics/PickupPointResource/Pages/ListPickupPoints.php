<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics\PickupPointResource\Pages;

use App\Filament\Tenant\Resources\Logistics\PickupPointResource;
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
