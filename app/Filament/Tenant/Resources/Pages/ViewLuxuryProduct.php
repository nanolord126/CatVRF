<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LuxuryProduct\Pages;

use use App\Filament\Tenant\Resources\LuxuryProductResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewLuxuryProduct extends ViewRecord
{
    protected static string $resource = LuxuryProductResource::class;

    public function getTitle(): string
    {
        return 'View LuxuryProduct';
    }
}