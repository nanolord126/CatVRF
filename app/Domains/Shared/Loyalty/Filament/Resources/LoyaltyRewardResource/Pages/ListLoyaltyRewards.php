<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\LoyaltyRewardResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Loyalty\Filament\Resources\LoyaltyRewardResource;

final class ListLoyaltyRewards extends ListRecords
{
    protected static string $resource = LoyaltyRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
