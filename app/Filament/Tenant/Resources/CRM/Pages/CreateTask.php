<?php

namespace App\Filament\Tenant\Resources\CRM\Pages;

use App\Filament\Tenant\Resources\CRM\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;
}
