<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\VenueResource\Pages;

use Modules\Hotels\Filament\Resources\VenueResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditVenue extends EditRecord
{
    protected static string $resource = VenueResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
