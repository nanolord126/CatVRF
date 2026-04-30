<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Prenatal\Pages;

use Modules\Fitness\Filament\Resources\Prenatal\PrenatalProgramResource;
use Filament\Resources\Pages\EditRecord;

final class EditPrenatalProgram extends EditRecord
{
    protected static string $resource = PrenatalProgramResource::class;
}
