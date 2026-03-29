<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalService\Pages;

use use App\Filament\Tenant\Resources\DentalServiceResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewDentalService extends ViewRecord
{
    protected static string $resource = DentalServiceResource::class;

    public function getTitle(): string
    {
        return 'View DentalService';
    }
}