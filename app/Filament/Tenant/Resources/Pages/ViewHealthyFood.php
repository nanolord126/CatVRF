<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HealthyFood\Pages;

use use App\Filament\Tenant\Resources\HealthyFoodResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewHealthyFood extends ViewRecord
{
    protected static string $resource = HealthyFoodResource::class;

    public function getTitle(): string
    {
        return 'View HealthyFood';
    }
}