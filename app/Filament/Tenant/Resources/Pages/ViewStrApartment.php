<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrApartment\Pages;

use use App\Filament\Tenant\Resources\StrApartmentResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewStrApartment extends ViewRecord
{
    protected static string $resource = StrApartmentResource::class;

    public function getTitle(): string
    {
        return 'View StrApartment';
    }
}