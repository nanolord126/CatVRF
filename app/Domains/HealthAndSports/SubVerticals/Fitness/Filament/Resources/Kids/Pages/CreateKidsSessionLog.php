<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids\Pages;

use Modules\Fitness\Filament\Resources\Kids\KidsSessionLogResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateKidsSessionLog extends CreateRecord
{
    protected static string $resource = KidsSessionLogResource::class;
}
