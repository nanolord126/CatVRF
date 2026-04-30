<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior\Pages;

use Modules\Fitness\Filament\Resources\Senior\SeniorProgramResource;
use Filament\Resources\Pages\ListRecords;

final class ListSeniorPrograms extends ListRecords
{
    protected static string $resource = SeniorProgramResource::class;
}
