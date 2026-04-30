<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\BookingResource\Pages;

use Modules\Hotels\Filament\Resources\BookingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
