<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Medical\Pages;

use use App\Filament\Tenant\Resources\MedicalResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewMedical extends ViewRecord
{
    protected static string $resource = MedicalResource::class;

    public function getTitle(): string
    {
        return 'View Medical';
    }
}