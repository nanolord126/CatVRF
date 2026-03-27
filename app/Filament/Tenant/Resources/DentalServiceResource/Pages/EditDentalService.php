<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalServiceResource\Pages;

use App\Filament\Tenant\Resources\DentalServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditDentalService extends EditRecord
{
    protected static string $resource = DentalServiceResource::class;

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
