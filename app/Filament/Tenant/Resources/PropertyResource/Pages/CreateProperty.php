<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PropertyResource\Pages;

use App\Filament\Tenant\Resources\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
