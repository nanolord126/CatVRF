<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\TrainerEffectivenessResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewTrainerEffectiveness extends ViewRecord
{
    protected static string $resource = \Modules\Fitness\Filament\Resources\TrainerEffectivenessResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
