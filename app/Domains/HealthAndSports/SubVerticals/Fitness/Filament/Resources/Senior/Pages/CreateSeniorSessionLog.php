<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior\Pages;

use Modules\Fitness\Filament\Resources\Senior\SeniorSessionLogResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSeniorSessionLog extends CreateRecord
{
    protected static string $resource = SeniorSessionLogResource::class;
}
