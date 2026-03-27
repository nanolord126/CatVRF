<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RentalContractResource\Pages;

use App\Filament\Tenant\Resources\RentalContractResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateRentalContract extends CreateRecord
{
    protected static string $resource = RentalContractResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
