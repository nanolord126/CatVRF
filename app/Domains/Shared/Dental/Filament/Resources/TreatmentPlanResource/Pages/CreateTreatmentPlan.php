<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources\TreatmentPlanResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Modules\Dental\Filament\Resources\TreatmentPlanResource;

final class CreateTreatmentPlan extends CreateRecord
{
    protected static string $resource = TreatmentPlanResource::class;
}
