<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Prenatal\Pages;

use Modules\Fitness\Filament\Resources\Prenatal\PrenatalSessionLogResource;
use Filament\Resources\Pages\EditRecord;

final class EditPrenatalSessionLog extends EditRecord
{
    protected static string $resource = PrenatalSessionLogResource::class;
}
