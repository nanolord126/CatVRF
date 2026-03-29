<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalRecord\Pages;

use use App\Filament\Tenant\Resources\MedicalRecordResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateMedicalRecord extends CreateRecord
{
    protected static string $resource = MedicalRecordResource::class;

    public function getTitle(): string
    {
        return 'Create MedicalRecord';
    }
}