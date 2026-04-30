<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources\TreatmentPlanResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Dental\Filament\Resources\TreatmentPlanResource;

final class ListTreatmentPlans extends ListRecords
{
    protected static string $resource = TreatmentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
