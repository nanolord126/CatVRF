<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior\Pages;

use Modules\Fitness\Filament\Resources\Senior\SeniorProgramResource;
use Filament\Resources\Pages\EditRecord;

final class EditSeniorProgram extends EditRecord
{
    protected static string $resource = SeniorProgramResource::class;
}
