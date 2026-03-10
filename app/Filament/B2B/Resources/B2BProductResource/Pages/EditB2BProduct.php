<?php

namespace App\Filament\B2B\Resources\B2BProductResource\Pages;

use App\Filament\B2B\Resources\B2BProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditB2BProduct extends EditRecord
{
    protected static string $resource = B2BProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
