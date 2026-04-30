<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\VenueResource\Pages;

use Modules\Hotels\Filament\Resources\VenueResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListVenues extends ListRecords
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
