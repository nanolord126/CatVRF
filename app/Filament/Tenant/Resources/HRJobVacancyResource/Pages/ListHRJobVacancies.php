<?php

namespace App\Filament\Tenant\Resources\HRJobVacancyResource\Pages;

use App\Filament\Tenant\Resources\HRJobVacancyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHRJobVacancies extends ListRecords
{
    protected static string $resource = HRJobVacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
