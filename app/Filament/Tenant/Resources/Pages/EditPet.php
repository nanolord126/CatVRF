<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pet\Pages;

use use App\Filament\Tenant\Resources\PetResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditPet extends EditRecord
{
    protected static string $resource = PetResource::class;

    public function getTitle(): string
    {
        return 'Edit Pet';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}