<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Furniture\Pages;

use use App\Filament\Tenant\Resources\FurnitureResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFurniture extends ViewRecord
{
    protected static string $resource = FurnitureResource::class;

    public function getTitle(): string
    {
        return 'View Furniture';
    }
}