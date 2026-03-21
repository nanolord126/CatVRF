<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ConstructionMaterials\Pages;

use App\Filament\Tenant\Resources\ConstructionMaterials\ConstructionMaterialResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateConstructionMaterial extends CreateRecord
{
    protected static string $resource = ConstructionMaterialResource::class;
}
