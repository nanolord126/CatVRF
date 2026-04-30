<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources\LoyaltyProgramResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Loyalty\Filament\Resources\LoyaltyProgramResource;

final class ListLoyaltyPrograms extends ListRecords
{
    protected static string $resource = LoyaltyProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
