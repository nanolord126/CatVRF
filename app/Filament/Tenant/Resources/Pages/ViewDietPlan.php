<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DietPlan\Pages;

use use App\Filament\Tenant\Resources\DietPlanResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewDietPlan extends ViewRecord
{
    protected static string $resource = DietPlanResource::class;

    public function getTitle(): string
    {
        return 'View DietPlan';
    }
}