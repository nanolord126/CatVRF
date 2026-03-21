<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureResource\Pages;

use App\Filament\Tenant\Resources\FurnitureResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFurniture extends CreateRecord
{
    protected static string $resource = FurnitureResource::class;
}
