<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior\Pages;

use Modules\Fitness\Filament\Resources\Senior\SeniorProgramEnrollmentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSeniorProgramEnrollment extends CreateRecord
{
    protected static string $resource = SeniorProgramEnrollmentResource::class;
}
