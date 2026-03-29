<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TaxiDriver\Pages;

use use App\Filament\Tenant\Resources\TaxiDriverResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewTaxiDriver extends ViewRecord
{
    protected static string $resource = TaxiDriverResource::class;

    public function getTitle(): string
    {
        return 'View TaxiDriver';
    }
}