<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\LashCertificationResource\Pages;

use Modules\BeautyMasters\Filament\Resources\LashCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateLashCertification extends CreateRecord
{
    protected static string $resource = LashCertificationResource::class;
}
