<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalServiceResource\Pages;

use App\Filament\Tenant\Resources\DentalServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateDentalService extends CreateRecord
{
    protected static string $resource = DentalServiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
