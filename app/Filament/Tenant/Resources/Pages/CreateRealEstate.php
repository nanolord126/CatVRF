<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate\Pages;

use use App\Filament\Tenant\Resources\RealEstateResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateRealEstate extends CreateRecord
{
    protected static string $resource = RealEstateResource::class;

    public function getTitle(): string
    {
        return 'Create RealEstate';
    }
}