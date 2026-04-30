<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Prenatal\Pages;

use Modules\Fitness\Filament\Resources\Prenatal\PrenatalEnrollmentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePrenatalEnrollment extends CreateRecord
{
    protected static string $resource = PrenatalEnrollmentResource::class;
}
