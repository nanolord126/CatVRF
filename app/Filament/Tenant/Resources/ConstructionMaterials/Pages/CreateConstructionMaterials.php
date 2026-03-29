<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\ConstructionMaterials\Pages;
use App\Filament\Tenant\Resources\ConstructionMaterialsResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordConstructionMaterials extends CreateRecord {
    protected static string $resource = ConstructionMaterialsResource::class;
}
