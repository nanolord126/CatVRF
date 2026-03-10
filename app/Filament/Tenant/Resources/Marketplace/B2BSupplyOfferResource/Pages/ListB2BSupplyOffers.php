<?php

namespace App\Filament\Tenant\Resources\Marketplace\B2BSupplyOfferResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\B2BSupplyOfferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListB2BSupplyOffers extends ListRecords
{
    protected static string $resource = B2BSupplyOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
