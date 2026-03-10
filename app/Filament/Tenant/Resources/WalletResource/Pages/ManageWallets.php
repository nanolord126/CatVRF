<?php

namespace App\Filament\Tenant\Resources\WalletResource\Pages;

use App\Filament\Tenant\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWallets extends ManageRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
