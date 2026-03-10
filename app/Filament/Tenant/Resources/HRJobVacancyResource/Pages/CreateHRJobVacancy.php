<?php

namespace App\Filament\Tenant\Resources\HRJobVacancyResource\Pages;

use App\Filament\Tenant\Resources\HRJobVacancyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHRJobVacancy extends CreateRecord
{
    protected static string $resource = HRJobVacancyResource::class;
}
