<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\LoyaltyTierResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Modules\Loyalty\Filament\Resources\LoyaltyTierResource;

final class CreateLoyaltyTier extends CreateRecord
{
    protected static string $resource = LoyaltyTierResource::class;
}
