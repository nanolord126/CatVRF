<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TreatmentPlan\Pages;

use use App\Filament\Tenant\Resources\TreatmentPlanResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateTreatmentPlan extends CreateRecord
{
    protected static string $resource = TreatmentPlanResource::class;

    public function getTitle(): string
    {
        return 'Create TreatmentPlan';
    }
}