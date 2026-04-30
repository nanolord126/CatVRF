<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids\Pages;

use Modules\Fitness\Filament\Resources\Kids\KidsHealthProfileResource;
use Filament\Resources\Pages\ListRecords;

final class ListKidsHealthProfiles extends ListRecords
{
    protected static string $resource = KidsHealthProfileResource::class;
}
