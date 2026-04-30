<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior\Pages;

use Modules\Fitness\Filament\Resources\Senior\SeniorProgramEnrollmentResource;
use Filament\Resources\Pages\ListRecords;

final class ListSeniorProgramEnrollments extends ListRecords
{
    protected static string $resource = SeniorProgramEnrollmentResource::class;
}
