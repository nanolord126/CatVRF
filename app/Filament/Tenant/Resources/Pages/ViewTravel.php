<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Travel\Pages;

use use App\Filament\Tenant\Resources\TravelResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewTravel extends ViewRecord
{
    protected static string $resource = TravelResource::class;

    public function getTitle(): string
    {
        return 'View Travel';
    }
}