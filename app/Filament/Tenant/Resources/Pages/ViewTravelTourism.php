<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TravelTourism\Pages;

use use App\Filament\Tenant\Resources\TravelTourismResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewTravelTourism extends ViewRecord
{
    protected static string $resource = TravelTourismResource::class;

    public function getTitle(): string
    {
        return 'View TravelTourism';
    }
}