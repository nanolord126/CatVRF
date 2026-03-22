<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiDriverResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiDriverResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTaxiDriver extends CreateRecord
{
    protected static string $resource = TaxiDriverResource::class;
}
