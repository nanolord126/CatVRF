<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotel\Pages;

use use App\Filament\Tenant\Resources\HotelResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewHotel extends ViewRecord
{
    protected static string $resource = HotelResource::class;

    public function getTitle(): string
    {
        return 'View Hotel';
    }
}