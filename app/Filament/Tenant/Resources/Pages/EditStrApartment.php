<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrApartment\Pages;

use use App\Filament\Tenant\Resources\StrApartmentResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditStrApartment extends EditRecord
{
    protected static string $resource = StrApartmentResource::class;

    public function getTitle(): string
    {
        return 'Edit StrApartment';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}