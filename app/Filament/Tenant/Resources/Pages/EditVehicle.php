<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Vehicle\Pages;

use use App\Filament\Tenant\Resources\VehicleResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    public function getTitle(): string
    {
        return 'Edit Vehicle';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}