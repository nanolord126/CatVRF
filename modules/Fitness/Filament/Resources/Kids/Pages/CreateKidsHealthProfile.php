<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids\Pages;

use Modules\Fitness\Filament\Resources\Kids\KidsHealthProfileResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateKidsHealthProfile extends CreateRecord
{
    protected static string $resource = KidsHealthProfileResource::class;
}
