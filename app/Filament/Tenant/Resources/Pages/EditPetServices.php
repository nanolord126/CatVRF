<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PetServices\Pages;

use use App\Filament\Tenant\Resources\PetServicesResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditPetServices extends EditRecord
{
    protected static string $resource = PetServicesResource::class;

    public function getTitle(): string
    {
        return 'Edit PetServices';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}