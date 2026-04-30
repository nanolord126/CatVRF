<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\MasterResource\Pages;

use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditMaster extends EditRecord
{
    protected static string $resource = \Modules\BeautyMasters\Filament\Resources\MasterResource::class;
}
