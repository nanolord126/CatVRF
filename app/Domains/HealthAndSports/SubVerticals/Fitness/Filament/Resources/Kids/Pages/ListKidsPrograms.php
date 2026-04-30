<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids\Pages;

use Modules\Fitness\Filament\Resources\Kids\KidsProgramResource;
use Filament\Resources\Pages\ListRecords;

final class ListKidsPrograms extends ListRecords
{
    protected static string $resource = KidsProgramResource::class;
}
