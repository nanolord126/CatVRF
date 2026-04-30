<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids\Pages;

use Modules\Fitness\Filament\Resources\Kids\KidsProgramEnrollmentResource;
use Filament\Resources\Pages\ListRecords;

final class ListKidsProgramEnrollments extends ListRecords
{
    protected static string $resource = KidsProgramEnrollmentResource::class;
}
