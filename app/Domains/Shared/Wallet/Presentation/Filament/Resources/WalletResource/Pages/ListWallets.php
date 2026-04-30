<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Filament\Resources\WalletResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Wallet\Presentation\Filament\Resources\WalletResource;

final class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
