<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureCustomOrder\Pages;

use use App\Filament\Tenant\Resources\FurnitureCustomOrderResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFurnitureCustomOrder extends ViewRecord
{
    protected static string $resource = FurnitureCustomOrderResource::class;

    public function getTitle(): string
    {
        return 'View FurnitureCustomOrder';
    }
}