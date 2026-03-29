<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ElectronicsProduct\Pages;

use use App\Filament\Tenant\Resources\ElectronicsProductResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateElectronicsProduct extends CreateRecord
{
    protected static string $resource = ElectronicsProductResource::class;

    public function getTitle(): string
    {
        return 'Create ElectronicsProduct';
    }
}