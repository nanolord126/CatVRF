<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TaxiDriverResource\Pages;

use App\Filament\Tenant\Resources\TaxiDriverResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTaxiDriver extends CreateRecord
{
    protected static string $resource = TaxiDriverResource::class;
}
