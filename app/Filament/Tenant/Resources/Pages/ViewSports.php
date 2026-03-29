<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Sports\Pages;

use use App\Filament\Tenant\Resources\SportsResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewSports extends ViewRecord
{
    protected static string $resource = SportsResource::class;

    public function getTitle(): string
    {
        return 'View Sports';
    }
}