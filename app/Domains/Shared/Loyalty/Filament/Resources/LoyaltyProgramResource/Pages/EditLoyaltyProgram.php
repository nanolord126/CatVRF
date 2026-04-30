<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\LoyaltyProgramResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Loyalty\Filament\Resources\LoyaltyProgramResource;

final class EditLoyaltyProgram extends EditRecord
{
    protected static string $resource = LoyaltyProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
