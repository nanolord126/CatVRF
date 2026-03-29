<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Medical\Pages;

use use App\Filament\Tenant\Resources\MedicalResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateMedical extends CreateRecord
{
    protected static string $resource = MedicalResource::class;

    public function getTitle(): string
    {
        return 'Create Medical';
    }
}