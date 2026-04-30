<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\LoyaltyProgramResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Modules\Loyalty\Filament\Resources\LoyaltyProgramResource;

final class CreateLoyaltyProgram extends CreateRecord
{
    protected static string $resource = LoyaltyProgramResource::class;
}
