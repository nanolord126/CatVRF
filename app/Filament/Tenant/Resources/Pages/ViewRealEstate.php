<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate\Pages;

use use App\Filament\Tenant\Resources\RealEstateResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewRealEstate extends ViewRecord
{
    protected static string $resource = RealEstateResource::class;

    public function getTitle(): string
    {
        return 'View RealEstate';
    }
}