<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids\Pages;

use Modules\Fitness\Filament\Resources\Kids\KidsHealthProfileResource;
use Filament\Resources\Pages\EditRecord;

final class EditKidsHealthProfile extends EditRecord
{
    protected static string $resource = KidsHealthProfileResource::class;
}
