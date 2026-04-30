<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\LoyaltyRuleResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Loyalty\Filament\Resources\LoyaltyRuleResource;

final class EditLoyaltyRule extends EditRecord
{
    protected static string $resource = LoyaltyRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
