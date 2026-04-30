<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages;

use Modules\Fitness\Filament\Resources\SeasonalPrograms\SeasonalProgramResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSeasonalProgram extends CreateRecord
{
    protected static string $resource = SeasonalProgramResource::class;
}
