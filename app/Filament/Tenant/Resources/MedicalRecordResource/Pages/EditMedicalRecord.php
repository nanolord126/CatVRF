<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalRecordResource\Pages;

use App\Filament\Tenant\Resources\MedicalRecordResource;
use Filament\Resources\Pages\EditRecord;

final class EditMedicalRecord extends EditRecord
{
    protected static string $resource = MedicalRecordResource::class;
}
