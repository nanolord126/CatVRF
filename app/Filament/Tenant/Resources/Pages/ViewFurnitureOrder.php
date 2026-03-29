<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureOrder\Pages;

use use App\Filament\Tenant\Resources\FurnitureOrderResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFurnitureOrder extends ViewRecord
{
    protected static string $resource = FurnitureOrderResource::class;

    public function getTitle(): string
    {
        return 'View FurnitureOrder';
    }
}