<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\LoyaltyRuleResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Loyalty\Filament\Resources\LoyaltyRuleResource;

final class ListLoyaltyRules extends ListRecords
{
    protected static string $resource = LoyaltyRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
