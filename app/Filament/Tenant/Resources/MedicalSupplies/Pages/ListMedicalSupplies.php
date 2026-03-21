<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalSupplies\Pages;

use App\Filament\Tenant\Resources\MedicalSupplies\MedicalSupplyResource;
use Filament\Resources\Pages\ListRecords;

final class ListMedicalSupplies extends ListRecords
{
    protected static string $resource = MedicalSupplyResource::class;
}
