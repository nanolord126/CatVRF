<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\VenueResource\Pages;

use Modules\Hotels\Filament\Resources\VenueResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewVenue extends ViewRecord
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
