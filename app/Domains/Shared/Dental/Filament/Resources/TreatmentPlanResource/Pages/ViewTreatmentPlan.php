<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources\TreatmentPlanResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Dental\Filament\Resources\TreatmentPlanResource;

final class ViewTreatmentPlan extends ViewRecord
{
    protected static string $resource = TreatmentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
