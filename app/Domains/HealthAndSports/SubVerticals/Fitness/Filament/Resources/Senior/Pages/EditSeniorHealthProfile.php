<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior\Pages;

use Modules\Fitness\Filament\Resources\Senior\SeniorHealthProfileResource;
use Filament\Resources\Pages\EditRecord;

final class EditSeniorHealthProfile extends EditRecord
{
    protected static string $resource = SeniorHealthProfileResource::class;
}
