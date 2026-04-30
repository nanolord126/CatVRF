<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages;

use Modules\Fitness\Filament\Resources\SeasonalPrograms\ProgramProgressLogResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProgramProgressLog extends CreateRecord
{
    protected static string $resource = ProgramProgressLogResource::class;
}
