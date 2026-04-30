<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Prenatal\Pages;

use Modules\Fitness\Filament\Resources\Prenatal\PrenatalEnrollmentResource;
use Filament\Resources\Pages\ListRecords;

final class ListPrenatalEnrollments extends ListRecords
{
    protected static string $resource = PrenatalEnrollmentResource::class;
}
