<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior\Pages;

use Modules\Fitness\Filament\Resources\Senior\SeniorSessionLogResource;
use Filament\Resources\Pages\EditRecord;

final class EditSeniorSessionLog extends EditRecord
{
    protected static string $resource = SeniorSessionLogResource::class;
}
