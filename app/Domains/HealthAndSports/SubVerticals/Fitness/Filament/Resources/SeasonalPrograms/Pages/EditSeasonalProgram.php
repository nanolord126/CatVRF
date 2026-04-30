<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages;

use Modules\Fitness\Filament\Resources\SeasonalPrograms\SeasonalProgramResource;
use Filament\Resources\Pages\EditRecord;

final class EditSeasonalProgram extends EditRecord
{
    protected static string $resource = SeasonalProgramResource::class;
}
