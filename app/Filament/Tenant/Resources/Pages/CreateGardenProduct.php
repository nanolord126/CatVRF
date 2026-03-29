<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\GardenProduct\Pages;

use use App\Filament\Tenant\Resources\GardenProductResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateGardenProduct extends CreateRecord
{
    protected static string $resource = GardenProductResource::class;

    public function getTitle(): string
    {
        return 'Create GardenProduct';
    }
}