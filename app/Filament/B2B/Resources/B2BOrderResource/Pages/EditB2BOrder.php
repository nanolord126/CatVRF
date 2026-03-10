<?php

namespace App\Filament\B2B\Resources\B2BOrderResource\Pages;

use App\Filament\B2B\Resources\B2BOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditB2BOrder extends EditRecord
{
    protected static string $resource = B2BOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
