<?php

declare(strict_types=1);

namespace Filament\Resources\LockedBonusBatchResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateLockedBonusBatch extends CreateRecord
{
    protected static string $resource = \Filament\Resources\LockedBonusBatchResource::class;
}
