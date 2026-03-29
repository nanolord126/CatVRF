<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalConsumable\Pages;

use use App\Filament\Tenant\Resources\DentalConsumableResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewDentalConsumable extends ViewRecord
{
    protected static string $resource = DentalConsumableResource::class;

    public function getTitle(): string
    {
        return 'View DentalConsumable';
    }
}