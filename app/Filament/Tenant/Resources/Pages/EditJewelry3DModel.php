<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Jewelry3DModel\Pages;

use use App\Filament\Tenant\Resources\Jewelry3DModelResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditJewelry3DModel extends EditRecord
{
    protected static string $resource = Jewelry3DModelResource::class;

    public function getTitle(): string
    {
        return 'Edit Jewelry3DModel';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}