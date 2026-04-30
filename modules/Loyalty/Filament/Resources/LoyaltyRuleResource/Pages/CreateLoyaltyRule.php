<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\LoyaltyRuleResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Modules\Loyalty\Filament\Resources\LoyaltyRuleResource;

final class CreateLoyaltyRule extends CreateRecord
{
    protected static string $resource = LoyaltyRuleResource::class;
}
