<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\BrowCertificationResource\Pages;

use Modules\BeautyMasters\Filament\Resources\BrowCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateBrowCertification extends CreateRecord
{
    protected static string $resource = BrowCertificationResource::class;
}
