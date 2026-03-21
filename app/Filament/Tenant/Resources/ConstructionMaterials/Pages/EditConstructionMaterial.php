<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ConstructionMaterials\Pages;

use App\Filament\Tenant\Resources\ConstructionMaterials\ConstructionMaterialResource;
use Filament\Resources\Pages\EditRecord;

final class EditConstructionMaterial extends EditRecord
{
    protected static string $resource = ConstructionMaterialResource::class;
}
