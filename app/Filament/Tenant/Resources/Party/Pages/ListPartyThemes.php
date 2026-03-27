<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\Pages;

use App\Filament\Tenant\Resources\Party\PartyThemeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * ListPartyThemes.
 * Festive atmosphere management.
 */
final class ListPartyThemes extends ListRecords
{
    protected static string $resource = PartyThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Theme Collection')
                ->icon('heroicon-o-sparkles'),
        ];
    }
}
