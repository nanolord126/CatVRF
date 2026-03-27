<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PropertyResource\Pages;

use App\Filament\Tenant\Resources\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
