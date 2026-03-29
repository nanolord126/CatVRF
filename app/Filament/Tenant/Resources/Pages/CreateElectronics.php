<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Electronics\Pages;

use use App\Filament\Tenant\Resources\ElectronicsResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateElectronics extends CreateRecord
{
    protected static string $resource = ElectronicsResource::class;

    public function getTitle(): string
    {
        return 'Create Electronics';
    }
}