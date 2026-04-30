<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\BeautyMasters\Filament\Resources\MasterResource;

final class EditMaster extends EditRecord
{
    protected static string $resource = MasterResource::class;
}
