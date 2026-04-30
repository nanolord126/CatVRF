<?php

declare(strict_types=1);

namespace Filament\Resources\LockedBonusBatchResource\Pages;

use App\Domains\Bonuses\Models\LockedBonusBatch;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewLockedBonusBatch extends ViewRecord
{
    protected static string $resource = \Filament\Resources\LockedBonusBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
