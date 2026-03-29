<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Dentist\Pages;

use use App\Filament\Tenant\Resources\DentistResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateDentist extends CreateRecord
{
    protected static string $resource = DentistResource::class;

    public function getTitle(): string
    {
        return 'Create Dentist';
    }
}