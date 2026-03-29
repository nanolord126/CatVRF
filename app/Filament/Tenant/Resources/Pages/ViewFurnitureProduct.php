<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureProduct\Pages;

use use App\Filament\Tenant\Resources\FurnitureProductResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFurnitureProduct extends ViewRecord
{
    protected static string $resource = FurnitureProductResource::class;

    public function getTitle(): string
    {
        return 'View FurnitureProduct';
    }
}