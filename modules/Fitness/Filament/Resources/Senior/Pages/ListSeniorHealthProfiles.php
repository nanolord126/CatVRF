<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior\Pages;

use Modules\Fitness\Filament\Resources\Senior\SeniorHealthProfileResource;
use Filament\Resources\Pages\ListRecords;

final class ListSeniorHealthProfiles extends ListRecords
{
    protected static string $resource = SeniorHealthProfileResource::class;
}
