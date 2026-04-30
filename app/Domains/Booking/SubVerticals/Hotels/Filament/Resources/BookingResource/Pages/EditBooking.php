<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\BookingResource\Pages;

use Modules\Hotels\Filament\Resources\BookingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
