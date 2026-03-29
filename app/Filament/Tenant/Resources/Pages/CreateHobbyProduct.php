<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HobbyProduct\Pages;

use use App\Filament\Tenant\Resources\HobbyProductResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateHobbyProduct extends CreateRecord
{
    protected static string $resource = HobbyProductResource::class;

    public function getTitle(): string
    {
        return 'Create HobbyProduct';
    }
}