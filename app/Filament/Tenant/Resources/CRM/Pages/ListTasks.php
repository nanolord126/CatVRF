<?php

namespace App\Filament\Tenant\Resources\CRM\Pages;

use App\Filament\Tenant\Resources\CRM\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
