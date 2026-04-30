<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\BeautyMasters\Filament\Resources\MasterResource;

final class CreateMaster extends CreateRecord
{
    protected static string $resource = MasterResource::class;
}
