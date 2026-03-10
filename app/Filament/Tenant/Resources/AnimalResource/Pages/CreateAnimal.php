<?php

namespace App\Filament\Tenant\Resources\AnimalResource\Pages;

use App\Filament\Tenant\Resources\AnimalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAnimal extends CreateRecord
{
    protected static string $resource = AnimalResource::class;
}
