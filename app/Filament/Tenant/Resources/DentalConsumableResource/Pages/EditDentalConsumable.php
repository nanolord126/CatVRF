<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalConsumableResource\Pages;

use App\Filament\Tenant\Resources\DentalConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditDentalConsumable extends EditRecord
{
    protected static string $resource = DentalConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
