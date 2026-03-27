<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RentalContractResource\Pages;

use App\Filament\Tenant\Resources\RentalContractResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditRentalContract extends EditRecord
{
    protected static string $resource = RentalContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
