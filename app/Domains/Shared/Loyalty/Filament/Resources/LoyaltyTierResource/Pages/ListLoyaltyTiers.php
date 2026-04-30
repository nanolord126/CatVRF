<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\LoyaltyTierResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Loyalty\Filament\Resources\LoyaltyTierResource;

final class ListLoyaltyTiers extends ListRecords
{
    protected static string $resource = LoyaltyTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
