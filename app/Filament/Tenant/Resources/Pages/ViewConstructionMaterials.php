<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ConstructionMaterials\Pages;

use use App\Filament\Tenant\Resources\ConstructionMaterialsResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewConstructionMaterials extends ViewRecord
{
    protected static string $resource = ConstructionMaterialsResource::class;

    public function getTitle(): string
    {
        return 'View ConstructionMaterials';
    }
}