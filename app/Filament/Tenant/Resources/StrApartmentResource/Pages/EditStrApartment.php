<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrApartmentResource\Pages;

use App\Filament\Tenant\Resources\StrApartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditStrApartment extends EditRecord
{
    protected static string $resource = StrApartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
