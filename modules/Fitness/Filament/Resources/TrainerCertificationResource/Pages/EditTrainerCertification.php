<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\TrainerCertificationResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditTrainerCertification extends EditRecord
{
    protected static string $resource = \Modules\Fitness\Filament\Resources\TrainerCertificationResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
