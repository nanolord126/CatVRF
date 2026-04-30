<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior\Pages;

use Modules\Fitness\Filament\Resources\Senior\SeniorHealthProfileResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSeniorHealthProfile extends CreateRecord
{
    protected static string $resource = SeniorHealthProfileResource::class;
}
