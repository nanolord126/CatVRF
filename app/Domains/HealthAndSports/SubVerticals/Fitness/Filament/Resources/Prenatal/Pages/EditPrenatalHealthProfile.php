<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Prenatal\Pages;

use Modules\Fitness\Filament\Resources\Prenatal\PrenatalHealthProfileResource;
use Filament\Resources\Pages\EditRecord;

final class EditPrenatalHealthProfile extends EditRecord
{
    protected static string $resource = PrenatalHealthProfileResource::class;
}
