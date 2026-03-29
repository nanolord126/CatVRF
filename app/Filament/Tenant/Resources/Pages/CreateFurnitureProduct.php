<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureProduct\Pages;

use use App\Filament\Tenant\Resources\FurnitureProductResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateFurnitureProduct extends CreateRecord
{
    protected static string $resource = FurnitureProductResource::class;

    public function getTitle(): string
    {
        return 'Create FurnitureProduct';
    }
}