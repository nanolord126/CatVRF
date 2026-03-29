<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pet\Pages;

use use App\Filament\Tenant\Resources\PetResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreatePet extends CreateRecord
{
    protected static string $resource = PetResource::class;

    public function getTitle(): string
    {
        return 'Create Pet';
    }
}