<?php

declare(strict_types=1);

namespace App\Filament\CRM\Resources\ManagerKPIResource\Pages;

use App\Filament\CRM\Resources\ManagerKPIResource;
use Filament\Resources\Pages\ListRecords;

class ListManagerKPIs extends ListRecords
{
    protected static string $resource = ManagerKPIResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Pages\Actions\CreateAction::make(),
        ];
    }
}
