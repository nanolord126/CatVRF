<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics\Pages;

use use App\Filament\Tenant\Resources\LogisticsResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewLogistics extends ViewRecord
{
    protected static string $resource = LogisticsResource::class;

    public function getTitle(): string
    {
        return 'View Logistics';
    }
}