<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Corporate\Pages;

use Modules\Fitness\Filament\Resources\Corporate\CorporateEnrollmentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCorporateEnrollment extends CreateRecord
{
    protected static string $resource = CorporateEnrollmentResource::class;
}
