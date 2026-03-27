<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentistResource\Pages;

use App\Filament\Tenant\Resources\DentistResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateDentist extends CreateRecord
{
    protected static string $resource = DentistResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
