<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;

use App\Filament\Tenant\Resources\RealEstate\PropertyResource;
use Filament\Resources\Pages\EditRecord;

final class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
