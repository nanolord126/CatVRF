<?php

namespace App\Filament\Tenant\Resources\CRM\Pages;

use App\Filament\Tenant\Resources\CRM\DealResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeal extends EditRecord
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
