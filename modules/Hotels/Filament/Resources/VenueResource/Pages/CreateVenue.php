<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\VenueResource\Pages;

use Modules\Hotels\Filament\Resources\VenueResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateVenue extends CreateRecord
{
    protected static string $resource = VenueResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
