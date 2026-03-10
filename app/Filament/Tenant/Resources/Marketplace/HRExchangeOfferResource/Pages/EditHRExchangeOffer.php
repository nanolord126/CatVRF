<?php

namespace App\Filament\Tenant\Resources\Marketplace\HRExchangeOfferResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\HRExchangeOfferResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHRExchangeOffer extends EditRecord
{
    protected static string $resource = HRExchangeOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
