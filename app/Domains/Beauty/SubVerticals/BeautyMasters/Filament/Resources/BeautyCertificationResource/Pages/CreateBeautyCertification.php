<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\BeautyCertificationResource\Pages;

use Modules\BeautyMasters\Filament\Resources\BeautyCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateBeautyCertification extends CreateRecord
{
    protected static string $resource = BeautyCertificationResource::class;
}
