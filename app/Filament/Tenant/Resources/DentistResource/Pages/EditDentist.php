<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentistResource\Pages;

use App\Filament\Tenant\Resources\DentistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditDentist extends EditRecord
{
    protected static string $resource = DentistResource::class;

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
