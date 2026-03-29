<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\OfficeCatering\Pages;

use use App\Filament\Tenant\Resources\OfficeCateringResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateOfficeCatering extends CreateRecord
{
    protected static string $resource = OfficeCateringResource::class;

    public function getTitle(): string
    {
        return 'Create OfficeCatering';
    }
}