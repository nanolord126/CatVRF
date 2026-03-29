<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ConstructionMaterials\Pages;

use use App\Filament\Tenant\Resources\ConstructionMaterialsResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditConstructionMaterials extends EditRecord
{
    protected static string $resource = ConstructionMaterialsResource::class;

    public function getTitle(): string
    {
        return 'Edit ConstructionMaterials';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}