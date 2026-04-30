<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\GuestLoyaltyProfileResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Loyalty\Filament\Resources\GuestLoyaltyProfileResource;

final class ViewGuestLoyaltyProfile extends ViewRecord
{
    protected static string $resource = GuestLoyaltyProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
