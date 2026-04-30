<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\TrainerCertificationResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateTrainerCertification extends CreateRecord
{
    protected static string $resource = \Modules\Fitness\Filament\Resources\TrainerCertificationResource::class;
}
