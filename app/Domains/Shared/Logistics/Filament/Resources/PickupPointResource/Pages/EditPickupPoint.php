<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources\PickupPointResource\Pages;

use App\Domains\Logistics\Filament\Resources\PickupPointResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditPickupPoint extends EditRecord
{
    protected static string $resource = PickupPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
