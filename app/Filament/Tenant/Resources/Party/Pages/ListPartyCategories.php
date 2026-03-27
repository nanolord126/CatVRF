<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\Pages;

use App\Filament\Tenant\Resources\Party\PartyCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * ListPartyCategories.
 * 2026 Canonical Vertical implementation.
 */
final class ListPartyCategories extends ListRecords
{
    protected static string $resource = PartyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Event Category')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
