<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalConsumableResource\Pages;

use App\Filament\Tenant\Resources\DentalConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateDentalConsumable extends CreateRecord
{
    protected static string $resource = DentalConsumableResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
