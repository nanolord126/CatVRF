<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Prenatal\Pages;

use Modules\Fitness\Filament\Resources\Prenatal\PrenatalHealthProfileResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePrenatalHealthProfile extends CreateRecord
{
    protected static string $resource = PrenatalHealthProfileResource::class;
}
