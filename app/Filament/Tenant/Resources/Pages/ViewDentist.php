<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Dentist\Pages;

use use App\Filament\Tenant\Resources\DentistResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewDentist extends ViewRecord
{
    protected static string $resource = DentistResource::class;

    public function getTitle(): string
    {
        return 'View Dentist';
    }
}