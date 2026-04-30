<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\MakeupCertificationResource\Pages;

use Modules\BeautyMasters\Filament\Resources\MakeupCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateMakeupCertification extends CreateRecord
{
    protected static string $resource = MakeupCertificationResource::class;
}
