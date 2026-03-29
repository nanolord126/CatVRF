<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Sports\Pages;

use use App\Filament\Tenant\Resources\SportsResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateSports extends CreateRecord
{
    protected static string $resource = SportsResource::class;

    public function getTitle(): string
    {
        return 'Create Sports';
    }
}