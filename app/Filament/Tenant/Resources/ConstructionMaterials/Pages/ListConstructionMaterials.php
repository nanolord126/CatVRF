<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ConstructionMaterials\Pages;

use App\Filament\Tenant\Resources\ConstructionMaterials\ConstructionMaterialResource;
use Filament\Resources\Pages\ListRecords;

final class ListConstructionMaterials extends ListRecords
{
    protected static string $resource = ConstructionMaterialResource::class;
}
