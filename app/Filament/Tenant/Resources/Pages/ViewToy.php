<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Toy\Pages;

use use App\Filament\Tenant\Resources\ToyResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewToy extends ViewRecord
{
    protected static string $resource = ToyResource::class;

    public function getTitle(): string
    {
        return 'View Toy';
    }
}