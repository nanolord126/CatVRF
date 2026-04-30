<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages;

use Modules\Fitness\Filament\Resources\SeasonalPrograms\ClientProgramEnrollmentResource;
use Filament\Resources\Pages\EditRecord;

final class EditClientProgramEnrollment extends EditRecord
{
    protected static string $resource = ClientProgramEnrollmentResource::class;
}
