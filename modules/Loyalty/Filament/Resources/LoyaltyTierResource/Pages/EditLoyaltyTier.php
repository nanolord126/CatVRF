<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\LoyaltyTierResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Loyalty\Filament\Resources\LoyaltyTierResource;

final class EditLoyaltyTier extends EditRecord
{
    protected static string $resource = LoyaltyTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
