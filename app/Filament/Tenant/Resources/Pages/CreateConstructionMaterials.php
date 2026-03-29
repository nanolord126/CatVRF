<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ConstructionMaterials\Pages;

use use App\Filament\Tenant\Resources\ConstructionMaterialsResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateConstructionMaterials extends CreateRecord
{
    protected static string $resource = ConstructionMaterialsResource::class;

    public function getTitle(): string
    {
        return 'Create ConstructionMaterials';
    }
}