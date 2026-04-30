<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids\Pages;

use Modules\Fitness\Filament\Resources\Kids\KidsProgramEnrollmentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateKidsProgramEnrollment extends CreateRecord
{
    protected static string $resource = KidsProgramEnrollmentResource::class;
}
