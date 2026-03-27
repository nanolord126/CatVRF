<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalRecordResource\Pages;

use App\Filament\Tenant\Resources\MedicalRecordResource;
use Filament\Resources\Pages\ListRecords;

final class ListMedicalRecords extends ListRecords
{
    protected static string $resource = MedicalRecordResource::class;
}
