<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\AdaptiveWorkoutPlanResource\Pages;

use App\Domains\Sports\Filament\Resources\AdaptiveWorkoutPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAdaptiveWorkoutPlan extends ViewRecord
{
    protected static string $resource = AdaptiveWorkoutPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
