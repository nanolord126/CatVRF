<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\LoyaltyRewardResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Modules\Loyalty\Filament\Resources\LoyaltyRewardResource;

final class CreateLoyaltyReward extends CreateRecord
{
    protected static string $resource = LoyaltyRewardResource::class;
}
