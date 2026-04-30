<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\GuestLoyaltyProfileResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Modules\Loyalty\Filament\Resources\GuestLoyaltyProfileResource;

final class CreateGuestLoyaltyProfile extends CreateRecord
{
    protected static string $resource = GuestLoyaltyProfileResource::class;
}
