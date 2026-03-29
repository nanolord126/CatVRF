<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\GardenProduct\Pages;

use use App\Filament\Tenant\Resources\GardenProductResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewGardenProduct extends ViewRecord
{
    protected static string $resource = GardenProductResource::class;

    public function getTitle(): string
    {
        return 'View GardenProduct';
    }
}