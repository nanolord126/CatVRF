<?php

declare(strict_types=1);

namespace Filament\Resources\LockedBonusBatchResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditLockedBonusBatch extends EditRecord
{
    protected static string $resource = \Filament\Resources\LockedBonusBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\SaveAction::make(),
        ];
    }
}
