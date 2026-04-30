<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids\Pages;

use Modules\Fitness\Filament\Resources\Kids\KidsProgramResource;
use Filament\Resources\Pages\EditRecord;

final class EditKidsProgram extends EditRecord
{
    protected static string $resource = KidsProgramResource::class;
}
